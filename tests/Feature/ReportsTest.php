<?php

namespace Tests\Feature;

use App\Models\Market;
use App\Models\MarketGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfReader\PdfReader;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_read_market_group_or_comprehensive_reports(): void
    {
        $market = Market::factory()->create();
        foreach ([route('reports.index'), route('reports.all'), route('reports.market', $market), route('reports.group', $market->group)] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_market_report_contains_contract_details_annual_history_and_escaped_notes(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12', 'Asia/Amman'));
        $market = Market::factory()->create(['name' => 'سوق جابر', 'lease_start_date' => '2022-03-01', 'lease_end_date' => '2025-03-01', 'description' => '<script>alert(1)</script>']);
        $this->actingAs(User::factory()->create())->get(route('reports.market', $market))->assertOk()
            ->assertSee('سوق جابر')->assertSee('01/03/2022')->assertSee('01/03/2025')
            ->assertSee('<td>2022</td>', false)->assertSee('<td>2024</td>', false)->assertDontSee('2022 / 2023')->assertSee('15,000.00')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_single_market_report_names_the_store_instead_of_counting_markets(): void
    {
        $market = Market::factory()->create(['name' => 'سوق جابر']);
        $this->actingAs(User::factory()->create())->get(route('reports.market', $market))->assertOk()
            ->assertSee('<strong class="store-name">سوق جابر</strong>', false)
            ->assertDontSee('عدد الأسواق')->assertDontSee('المدفوع: 0.00 د.أ')->assertDontSee('«بلا تفاصيل سداد» لا تعني مدفوعاً');
        $this->get(route('reports.group', $market->group))->assertOk()->assertSee('عدد الأسواق')->assertSee('«بلا تفاصيل سداد» لا تعني مدفوعاً');
    }

    public function test_report_lists_each_section_of_a_split_market(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12', 'Asia/Amman'));
        $market = Market::factory()->create(['lease_start_date' => '2025-10-08', 'lease_end_date' => '2027-10-08']);
        $market->sections()->create(['name' => 'القادمون', 'area' => 60, 'annual_rent' => 3000]);
        $market->sections()->create(['name' => 'المغادرون', 'area' => 40, 'annual_rent' => 2000, 'sort_order' => 1]);
        $this->actingAs(User::factory()->create())->get(route('reports.market', $market))->assertOk()
            ->assertSee('سوقان: القادمون والمغادرون')->assertSee('سوق القادمون')->assertSee('سوق المغادرون')
            ->assertSee('إجمالي سوق القادمون: 6,000.00 د.أ')->assertSee('إجمالي سوق المغادرون: 4,000.00 د.أ')->assertSee('إجمالي السوق: 10,000.00 د.أ')
            ->assertSee('الإيجار السنوي قبل خمس سنوات')->assertDontSee('تكوين السوق')->assertDontSee('مصدر البيانات')->assertDontSee('بلا تفاصيل سداد: ');
        $this->get(route('reports.market', ['market' => $market, 'format' => 'pdf']))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_group_report_only_includes_markets_from_that_group(): void
    {
        $group = MarketGroup::factory()->create(['name' => 'أسواق الشمال']);
        Market::factory()->create(['market_group_id' => $group->id, 'name' => 'سوق جابر']);
        Market::factory()->create(['name' => 'سوق العقبة']);
        $this->actingAs(User::factory()->create())->get(route('reports.group', $group))->assertOk()->assertSee('سوق جابر')->assertDontSee('سوق العقبة');
        $this->get(route('reports.all'))->assertOk()->assertSee('سوق جابر')->assertSee('سوق العقبة');
    }

    public function test_pdf_exports_work_for_a_market_group_and_all_markets(): void
    {
        $market = Market::factory()->create(['name' => 'سوق جابر']);
        $this->actingAs(User::factory()->create());
        foreach ([route('reports.market', ['market' => $market, 'format' => 'pdf']), route('reports.group', ['marketGroup' => $market->group, 'format' => 'pdf']), route('reports.all', ['format' => 'pdf'])] as $url) {
            $response = $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-', $response->getContent());
            $this->assertGreaterThan(10000, strlen($response->getContent()));
            $pages = (new PdfReader(new PdfParser(StreamReader::createByString($response->getContent()))))->getPageCount();
            $this->assertLessThanOrEqual(4, $pages, 'A short market report must fit into a few readable pages.');
        }
    }

    public function test_open_contract_report_stops_at_current_rental_year(): void
    {
        $this->travelTo(Carbon::parse('2026-02-01', 'Asia/Amman'));
        $market = Market::factory()->create(['lease_start_date' => '2024-03-01', 'lease_end_date' => null]);
        $this->actingAs(User::factory()->create())->get(route('reports.market', $market))->assertOk()->assertSee('<td>2025</td>', false)->assertDontSee('<td>2026</td>', false);
    }

    public function test_pdf_report_includes_photos_for_multiple_markets_without_excessive_pages(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12', 'Asia/Amman'));
        Storage::fake('public');
        $path = UploadedFile::fake()->image('market.jpg', 1600, 900)->store('markets', 'public');
        Market::factory()->count(3)->create(['image_path' => $path]);
        $response = $this->actingAs(User::factory()->create())->get(route('reports.all', ['format' => 'pdf']))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('/Subtype /Image', $response->getContent());
        $pages = (new PdfReader(new PdfParser(StreamReader::createByString($response->getContent()))))->getPageCount();
        $this->assertLessThanOrEqual(7, $pages);
    }
}
