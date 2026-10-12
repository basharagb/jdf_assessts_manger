<?php

namespace Tests\Feature;

use App\AnnualLease;
use App\Models\Market;
use App\Models\MarketGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnnualRentTest extends TestCase
{
    use RefreshDatabase;

    public function test_contract_end_uses_selected_year_and_same_day_and_month(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12', 'Asia/Amman'));
        Storage::fake('public');
        $group = MarketGroup::factory()->create();
        $response = $this->actingAs(User::factory()->create())->post(route('markets.store', $group), [
            'name' => 'سوق جابر', 'area' => 100, 'lessor' => 'الجهة المؤجرة', 'annual_rent' => 5000,
            'lease_start_date' => '2022-03-01', 'lease_end_year' => 2025,
            'image' => UploadedFile::fake()->image('market.jpg'), 'is_active' => 1,
        ]);
        $response->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('markets', ['name' => 'سوق جابر', 'annual_rent' => 5000, 'lease_end_date' => '2025-03-01']);
        Storage::disk('public')->assertExists(Market::first()->image_path);
    }

    public function test_new_contract_older_than_five_years_is_rejected(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12', 'Asia/Amman'));
        $group = MarketGroup::factory()->create();
        $this->actingAs(User::factory()->create())->post(route('markets.store', $group), [
            'name' => 'سوق جابر', 'area' => 100, 'lessor' => 'الجهة المؤجرة', 'annual_rent' => 5000,
            'lease_start_date' => '2021-10-11', 'lease_end_year' => 2027,
            'image' => UploadedFile::fake()->image('market.jpg'),
        ])->assertSessionHasErrors(['lease_start_date' => 'يمكن إدخال الإيجار بأثر رجعي حتى خمس سنوات فقط.']);
        $this->assertDatabaseCount('markets', 0);
    }

    public function test_end_year_must_be_after_start_year(): void
    {
        $market = Market::factory()->create(['lease_start_date' => '2024-03-01']);
        $this->actingAs(User::factory()->create())->put(route('markets.update', $market), [
            'name' => $market->name, 'area' => 100, 'lessor' => $market->lessor, 'annual_rent' => 5000,
            'lease_start_date' => '2024-03-01', 'lease_end_year' => 2024,
        ])->assertSessionHasErrors(['lease_end_year']);
        $this->assertSame(5000.0, (float) $market->fresh()->annual_rent);
    }

    public function test_calculator_counts_annual_periods_once_and_excludes_expiry_anniversary(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12', 'Asia/Amman'));
        $market = Market::factory()->create(['lease_start_date' => '2022-03-01', 'lease_end_date' => '2025-03-01']);
        $market->rentPeriods()->create(['start_year' => 2022, 'end_year' => 2023, 'annual_rent' => 5000, 'status' => 'paid']);
        $market->rentPeriods()->create(['start_year' => 2023, 'end_year' => 2024, 'annual_rent' => 6000, 'status' => 'unpaid']);
        $market->rentPeriods()->create(['start_year' => 2024, 'end_year' => 2025, 'annual_rent' => 0, 'status' => 'vacant']);
        $this->actingAs(User::factory()->create())->get(route('markets.show', ['market' => $market, 'from' => 2022, 'to' => 2025]))
            ->assertOk()->assertViewHas('totals', ['years' => 3, 'total' => 11000, 'paid' => 5000, 'unpaid' => 6000, 'untracked' => 0, 'vacant' => 1]);
        $this->assertSame(0.0, $market->currentAnnualRent());
    }

    public function test_leap_day_anniversary_recovers_february_29_in_leap_years(): void
    {
        $market = Market::factory()->create(['lease_start_date' => '2024-02-29', 'lease_end_date' => null]);
        $this->assertSame('2025-02-28', AnnualLease::anniversary($market, 2025)->toDateString());
        $this->assertSame('2028-02-29', AnnualLease::anniversary($market, 2028)->toDateString());
    }

    public function test_current_rent_follows_anniversary_instead_of_calendar_year(): void
    {
        $this->travelTo(Carbon::parse('2026-02-01', 'Asia/Amman'));
        $market = Market::factory()->create(['lease_start_date' => '2024-03-01', 'lease_end_date' => '2027-03-01']);
        $market->rentPeriods()->create(['start_year' => 2025, 'end_year' => 2026, 'annual_rent' => 7000, 'status' => 'paid']);
        $market->rentPeriods()->create(['start_year' => 2026, 'end_year' => null, 'annual_rent' => 9000, 'status' => 'paid']);
        $this->assertSame(7000.0, $market->currentAnnualRent());
        $this->travelTo(Carbon::parse('2026-03-01', 'Asia/Amman'));
        $this->assertSame(9000.0, $market->currentAnnualRent());
    }

    public function test_overlapping_years_are_rejected(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12', 'Asia/Amman'));
        $market = Market::factory()->create(['lease_start_date' => '2024-03-01', 'lease_end_date' => '2028-03-01']);
        $market->rentPeriods()->create(['start_year' => 2024, 'end_year' => 2026, 'annual_rent' => 5000, 'status' => 'paid']);
        $this->actingAs(User::factory()->create())->post(route('markets.rent-details.store', $market), [
            'start_year' => 2025, 'end_year' => 2027, 'annual_rent' => 7000, 'status' => 'paid',
        ])->assertSessionHasErrors(['start_year']);
        $this->assertDatabaseCount('market_rent_periods', 1);
    }

    public function test_next_period_can_start_in_the_year_the_previous_one_ends(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12', 'Asia/Amman'));
        $market = Market::factory()->create(['lease_start_date' => '2021-10-08', 'lease_end_date' => '2027-10-08']);
        $this->actingAs(User::factory()->create());
        $this->post(route('markets.rent-details.store', $market), ['start_year' => 2022, 'end_year' => 2023, 'annual_rent' => 5000, 'status' => 'paid'])->assertSessionHasNoErrors();
        $this->post(route('markets.rent-details.store', $market), ['start_year' => 2023, 'end_year' => 2024, 'annual_rent' => 5200, 'status' => 'unpaid'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('market_rent_periods', 2);
        $this->get(route('markets.show', ['market' => $market, 'from' => 2022, 'to' => 2024]))
            ->assertOk()->assertViewHas('totals', ['years' => 2, 'total' => 10200, 'paid' => 5000, 'unpaid' => 5200, 'untracked' => 0, 'vacant' => 0]);
    }

    public function test_period_end_year_must_follow_its_start_year(): void
    {
        $market = Market::factory()->create(['lease_start_date' => '2024-03-01', 'lease_end_date' => '2028-03-01']);
        $this->actingAs(User::factory()->create())->post(route('markets.rent-details.store', $market), [
            'start_year' => 2025, 'end_year' => 2025, 'annual_rent' => 5000, 'status' => 'paid',
        ])->assertSessionHasErrors(['end_year']);
        $this->assertDatabaseCount('market_rent_periods', 0);
    }

    public function test_rent_period_is_saved_annually_and_vacancy_has_no_rent(): void
    {
        $market = Market::factory()->create(['lease_start_date' => '2024-03-01', 'lease_end_date' => '2028-03-01']);
        $this->actingAs(User::factory()->create())->post(route('markets.rent-details.store', $market), [
            'start_year' => 2025, 'end_year' => 2026, 'status' => 'vacant',
        ])->assertSessionHasNoErrors()->assertRedirect(route('markets.rent-details.index', $market));
        $this->assertDatabaseHas('market_rent_periods', ['market_id' => $market->id, 'start_year' => 2025, 'annual_rent' => 0]);
    }

    public function test_period_from_another_market_cannot_be_edited(): void
    {
        $market = Market::factory()->create();
        $other = Market::factory()->create();
        $period = $other->rentPeriods()->create(['start_year' => 2025, 'annual_rent' => 5000, 'status' => 'paid']);
        $this->actingAs(User::factory()->create())->put(route('markets.rent-details.update', [$market, $period]), [
            'start_year' => 2025, 'status' => 'vacant',
        ])->assertNotFound();
        $this->assertDatabaseHas('market_rent_periods', ['id' => $period->id, 'status' => 'paid']);
    }

    public function test_rental_forms_render_year_selects_and_complete_start_date(): void
    {
        $market = Market::factory()->create();
        $this->actingAs(User::factory()->create())->get(route('markets.edit', $market))->assertOk()->assertSee('name="lease_end_year"', false)->assertSee('type="date"', false)->assertDontSee('monthly_rent');
        $this->get(route('markets.rent-details.create', $market))->assertOk()->assertSee('name="start_year"', false)->assertDontSee('2024 / 2025');
        $this->get(route('dashboard'))->assertOk()->assertSee('إجمالي الإيجار السنوي');
    }

    public function test_exact_five_year_boundary_is_allowed(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12', 'Asia/Amman'));
        Storage::fake('public');
        $group = MarketGroup::factory()->create();
        $this->actingAs(User::factory()->create())->post(route('markets.store', $group), [
            'name' => 'سوق الحدود', 'area' => 100, 'lessor' => 'الجهة المؤجرة', 'annual_rent' => 5000,
            'lease_start_date' => '2021-10-12', 'lease_end_year' => 2027,
            'image' => UploadedFile::fake()->image('market.jpg'),
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('markets', ['lease_start_date' => '2021-10-12', 'lease_end_date' => '2027-10-12']);
    }

    public function test_new_rent_period_older_than_five_years_is_rejected(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12', 'Asia/Amman'));
        $market = Market::factory()->create(['lease_start_date' => '2020-03-01', 'lease_end_date' => '2028-03-01']);
        $this->actingAs(User::factory()->create())->post(route('markets.rent-details.store', $market), [
            'start_year' => 2021, 'end_year' => 2022, 'annual_rent' => 5000, 'status' => 'paid',
        ])->assertSessionHasErrors(['start_year' => 'يمكن التسجيل بأثر رجعي حتى خمس سنوات فقط.']);
        $this->assertDatabaseCount('market_rent_periods', 0);
    }

    public function test_period_can_end_on_contract_expiry_but_not_after_it(): void
    {
        $market = Market::factory()->create(['lease_start_date' => '2024-03-01', 'lease_end_date' => '2027-03-01']);
        $this->actingAs(User::factory()->create())->post(route('markets.rent-details.store', $market), [
            'start_year' => 2025, 'end_year' => 2028, 'annual_rent' => 5000, 'status' => 'paid',
        ])->assertSessionHasErrors(['end_year']);
        $this->assertDatabaseCount('market_rent_periods', 0);
        $this->post(route('markets.rent-details.store', $market), [
            'start_year' => 2025, 'end_year' => 2027, 'annual_rent' => 5000, 'status' => 'paid',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('market_rent_periods', ['market_id' => $market->id, 'start_year' => 2025, 'end_year' => 2027]);
    }
}
