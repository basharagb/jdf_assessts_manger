<?php

namespace Tests\Feature;

use App\Models\Market;
use App\Models\MarketGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MarketSectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_market_with_two_sections_stores_each_section_and_their_totals(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12', 'Asia/Amman'));
        Storage::fake('public');
        $group = MarketGroup::factory()->create();
        $this->actingAs(User::factory()->create())->post(route('markets.store', $group), [
            'name' => 'سوق جابر', 'lessor' => 'الجهة المؤجرة', 'lease_start_date' => '2022-10-08', 'lease_end_year' => 2028,
            'section_count' => 2, 'area' => 999, 'annual_rent' => 999,
            'sections' => [
                ['name' => 'القادمون', 'area' => 120.5, 'annual_rent' => 6000],
                ['name' => 'المغادرون', 'area' => 80, 'annual_rent' => 4500.25],
            ],
            'image' => UploadedFile::fake()->image('market.jpg'),
        ])->assertSessionHasNoErrors()->assertRedirect();

        $market = Market::with('sections')->firstOrFail();
        $this->assertSame(['القادمون', 'المغادرون'], $market->sections->pluck('name')->all());
        $this->assertSame('200.50', $market->area);
        $this->assertSame('10500.25', $market->annual_rent);
        $this->assertSame(10500.25, $market->currentAnnualRent());
    }

    public function test_split_market_requires_a_name_area_and_rent_for_every_section(): void
    {
        $group = MarketGroup::factory()->create();
        $this->actingAs(User::factory()->create())->post(route('markets.store', $group), [
            'name' => 'سوق العمري', 'lessor' => 'الجهة المؤجرة', 'lease_start_date' => now()->toDateString(),
            'section_count' => 3,
            'sections' => [['name' => 'القادمون', 'area' => 50, 'annual_rent' => 2000], ['name' => 'القادمون', 'area' => 50, 'annual_rent' => 2000]],
            'image' => UploadedFile::fake()->image('market.jpg'),
        ])->assertSessionHasErrors(['sections', 'sections.0.name']);
        $this->assertDatabaseCount('markets', 0);
    }

    public function test_each_section_keeps_its_own_rent_periods(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12', 'Asia/Amman'));
        $market = Market::factory()->create(['lease_start_date' => '2022-10-08', 'lease_end_date' => '2028-10-08']);
        [$arrivals, $departures] = [$market->sections()->create(['name' => 'القادمون', 'area' => 60, 'annual_rent' => 3000]), $market->sections()->create(['name' => 'المغادرون', 'area' => 40, 'annual_rent' => 2000, 'sort_order' => 1])];
        $this->actingAs(User::factory()->create());

        $this->post(route('markets.rent-details.store', $market), ['market_section_id' => $arrivals->id, 'start_year' => 2023, 'end_year' => 2024, 'annual_rent' => 3100, 'status' => 'paid'])->assertSessionHasNoErrors();
        $this->post(route('markets.rent-details.store', $market), ['market_section_id' => $departures->id, 'start_year' => 2023, 'end_year' => 2024, 'annual_rent' => 2100, 'status' => 'unpaid'])->assertSessionHasNoErrors();
        $this->post(route('markets.rent-details.store', $market), ['market_section_id' => $arrivals->id, 'start_year' => 2023, 'end_year' => 2025, 'annual_rent' => 3200, 'status' => 'paid'])->assertSessionHasErrors(['start_year']);
        $this->post(route('markets.rent-details.store', $market), ['start_year' => 2024, 'end_year' => 2025, 'annual_rent' => 3200, 'status' => 'paid'])->assertSessionHasErrors(['market_section_id']);
        $this->assertDatabaseCount('market_rent_periods', 2);

        $this->get(route('markets.show', ['market' => $market, 'from' => 2023, 'to' => 2025]))->assertOk()
            ->assertViewHas('totals', ['years' => 2, 'total' => 10200, 'paid' => 3100, 'unpaid' => 2100, 'untracked' => 5000, 'vacant' => 0])
            ->assertSee('أقسام السوق')->assertSee('القادمون')->assertSee('المغادرون');
        $this->get(route('markets.rent-details.index', $market))->assertOk()->assertSee('قسم القادمون')->assertSee('قسم المغادرون');
    }

    public function test_splitting_a_market_shares_recorded_periods_by_section_rent(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12', 'Asia/Amman'));
        $market = Market::factory()->create(['lease_start_date' => '2022-10-08', 'lease_end_date' => null, 'annual_rent' => 5000]);
        $market->rentPeriods()->create(['start_year' => 2022, 'end_year' => 2023, 'annual_rent' => 5000, 'status' => 'paid']);

        $this->actingAs(User::factory()->create())->put(route('markets.update', $market), [
            'name' => $market->name, 'lessor' => $market->lessor, 'lease_start_date' => '2022-10-08',
            'section_count' => 2,
            'sections' => [['name' => 'القادمون', 'area' => 70, 'annual_rent' => 3000], ['name' => 'المغادرون', 'area' => 30, 'annual_rent' => 1000]],
        ])->assertSessionHasNoErrors()->assertRedirect(route('markets.show', $market));

        $market->refresh()->load('sections');
        $this->assertSame('4000.00', $market->annual_rent);
        $this->assertDatabaseCount('market_rent_periods', 2);
        $this->assertDatabaseMissing('market_rent_periods', ['market_section_id' => null]);
        $this->assertDatabaseHas('market_rent_periods', ['market_section_id' => $market->sections[0]->id, 'start_year' => 2022, 'end_year' => 2023, 'annual_rent' => 3750, 'status' => 'paid']);
        $this->assertDatabaseHas('market_rent_periods', ['market_section_id' => $market->sections[1]->id, 'start_year' => 2022, 'end_year' => 2023, 'annual_rent' => 1250, 'status' => 'paid']);
    }

    public function test_section_with_rent_periods_cannot_be_merged_into_a_single_unit(): void
    {
        $market = Market::factory()->create(['lease_start_date' => now()->subYear()->toDateString()]);
        $section = $market->sections()->create(['name' => 'القادمون', 'area' => 60, 'annual_rent' => 3000]);
        $market->sections()->create(['name' => 'المغادرون', 'area' => 40, 'annual_rent' => 2000, 'sort_order' => 1]);
        $market->rentPeriods()->create(['market_section_id' => $section->id, 'start_year' => now()->subYear()->year, 'end_year' => now()->year, 'annual_rent' => 3000, 'status' => 'paid']);

        $this->actingAs(User::factory()->create())->put(route('markets.update', $market), [
            'name' => $market->name, 'lessor' => $market->lessor, 'lease_start_date' => $market->lease_start_date->toDateString(),
            'section_count' => 1, 'area' => 100, 'annual_rent' => 5000,
        ])->assertSessionHasErrors(['section_count']);
        $this->assertDatabaseCount('market_sections', 2);
    }

    public function test_known_multi_hall_markets_are_split_without_changing_their_totals(): void
    {
        $market = Market::factory()->create(['name' => 'سوق العمري (القادمون والمغادرون والشحن)', 'area' => 100, 'annual_rent' => 5000]);
        $single = Market::factory()->create(['name' => 'سوق البوليفارد — العبدلي']);
        $market->rentPeriods()->create(['start_year' => 2024, 'end_year' => 2025, 'annual_rent' => 5200, 'status' => 'paid']);

        (require database_path('migrations/2026_10_12_203550_split_multi_section_markets.php'))->up();

        $sections = $market->sections()->get();
        $this->assertSame(['القادمون', 'المغادرون', 'الشحن'], $sections->pluck('name')->all());
        $this->assertEqualsWithDelta(100, $sections->sum('area'), 0.001);
        $this->assertEqualsWithDelta(5000, $sections->sum('annual_rent'), 0.001);
        $this->assertEqualsWithDelta(5200, $market->rentPeriods()->sum('annual_rent'), 0.001);
        $this->assertSame(3, $market->rentPeriods()->whereNotNull('market_section_id')->count());
        $this->assertFalse($single->sections()->exists());
    }

    public function test_single_year_periods_move_their_end_to_the_next_anniversary(): void
    {
        $market = Market::factory()->create();
        $sameYear = $market->rentPeriods()->create(['start_year' => 2024, 'end_year' => 2024, 'annual_rent' => 5000, 'status' => 'paid']);
        $range = $market->rentPeriods()->create(['start_year' => 2025, 'end_year' => 2026, 'annual_rent' => 5000, 'status' => 'paid']);

        (require database_path('migrations/2026_10_12_203549_use_boundary_years_for_rent_periods.php'))->up();

        $this->assertSame(2025, $sameYear->fresh()->end_year);
        $this->assertSame(2026, $range->fresh()->end_year);
    }

    public function test_market_expansion_adds_its_rent_from_its_start_year(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12', 'Asia/Amman'));
        $market = Market::factory()->create(['lease_start_date' => '2023-10-08', 'lease_end_date' => '2028-10-08', 'annual_rent' => 5000]);
        $section = $market->sections()->create(['name' => 'القادمون', 'area' => 60, 'annual_rent' => 3000]);
        $market->sections()->create(['name' => 'المغادرون', 'area' => 40, 'annual_rent' => 2000, 'sort_order' => 1]);
        $this->actingAs(User::factory()->create());

        $this->post(route('markets.expansions.store', $market), ['start_year' => 2025, 'added_annual_rent' => 1500])->assertSessionHasErrors(['market_section_id']);
        $this->post(route('markets.expansions.store', $market), ['market_section_id' => $section->id, 'start_year' => 2025, 'added_annual_rent' => 1500, 'added_area' => 20])
            ->assertSessionHasNoErrors()->assertRedirect(route('markets.expansions.index', $market));

        $this->get(route('markets.show', ['market' => $market, 'from' => 2024, 'to' => 2026]))->assertOk()
            ->assertViewHas('totals', fn (array $totals): bool => (float) $totals['total'] === 11500.0);
        $this->assertSame(6500.0, $market->fresh()->currentAnnualRent());
        $this->get(route('markets.expansions.index', $market))->assertOk()->assertSee('+1,500.00');
        $this->get(route('reports.market', $market))->assertOk()->assertSee('توسعات السوق — القادمون')->assertSee('منها توسعة 1,500.00');
    }

    public function test_expansion_of_another_market_cannot_be_deleted(): void
    {
        $market = Market::factory()->create();
        $expansion = Market::factory()->create()->expansions()->create(['start_year' => now()->year, 'added_annual_rent' => 1000]);
        $this->actingAs(User::factory()->create())->delete(route('markets.expansions.destroy', [$market, $expansion]))->assertNotFound();
        $this->assertModelExists($expansion);
    }

    public function test_demo_and_photo_source_sentences_are_removed_from_market_notes(): void
    {
        $market = Market::factory()->create(['description' => 'موقع حقيقي: يقع سوق جابر شمال عمّان. المساحة والجهة المؤجرة والإيجار وتاريخ العقد بيانات ديمو. الصورة من زيارة منشورة لسوق جابر على موقع JDF.']);
        $custom = Market::factory()->create(['description' => 'ملاحظة كتبها المستخدم.']);

        (require database_path('migrations/2026_10_13_102857_remove_demo_notes_from_market_descriptions.php'))->up();

        $this->assertSame('موقع حقيقي: يقع سوق جابر شمال عمّان.', $market->fresh()->description);
        $this->assertSame('ملاحظة كتبها المستخدم.', $custom->fresh()->description);
    }
}
