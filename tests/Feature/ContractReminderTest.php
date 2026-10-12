<?php

namespace Tests\Feature;

use App\Models\ContractReminder;
use App\Models\Market;
use App\Models\User;
use App\Models\WhatsAppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ContractReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.whatsapp.url' => 'http://whatsapp.test', 'services.whatsapp.api_key' => 'test-key']);
        Http::preventStrayRequests();
    }

    public function test_reminders_start_at_90_days_and_send_once_to_the_fixed_recipient(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12', 'Asia/Amman'));
        $market = Market::factory()->create(['lease_end_date' => '2027-01-10']);
        Market::factory()->create(['lease_end_date' => '2027-01-11']);
        Market::factory()->create(['lease_end_date' => '2027-01-10', 'is_active' => false]);
        Market::factory()->create(['lease_end_date' => '2026-10-11']);
        Market::factory()->create(['lease_end_date' => null]);
        Http::fake(['whatsapp.test/*' => Http::response(['success' => true, 'messageId' => 'message-1'])]);
        $this->artisan('contracts:send-reminders')->assertSuccessful();
        $this->artisan('contracts:send-reminders')->assertSuccessful();
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request['to'] === '962770911991' && $request->hasHeader('x-api-key', 'test-key') && $request->hasHeader('Idempotency-Key') && str_contains($request['message'], '90'));
        $this->assertDatabaseHas('contract_reminders', ['market_id' => $market->id, 'status' => 'sent', 'message_id' => 'message-1', 'attempts' => 1]);
    }

    public function test_partial_delivery_retries_only_the_failed_recipient(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12', 'Asia/Amman'));
        Market::factory()->create(['lease_end_date' => '2027-01-01']);
        WhatsAppSetting::create(['id' => 1, 'enabled' => true, 'additional_numbers' => ['962790000001']]);
        Http::fakeSequence()->push(['success' => true, 'messageId' => 'primary'])->push(['error' => 'Not connected'], 503)->push(['success' => true, 'messageId' => 'additional']);
        $this->artisan('contracts:send-reminders')->assertFailed();
        $this->assertDatabaseHas('contract_reminders', ['recipient' => '962770911991', 'status' => 'sent']);
        $this->assertDatabaseHas('contract_reminders', ['recipient' => '962790000001', 'status' => 'failed']);
        $this->artisan('contracts:send-reminders')->assertSuccessful();
        Http::assertSentCount(3);
        $this->assertDatabaseHas('contract_reminders', ['recipient' => '962790000001', 'status' => 'sent', 'attempts' => 2]);
    }

    public function test_uncertain_delivery_is_not_sent_again_without_review(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12', 'Asia/Amman'));
        Market::factory()->create(['lease_end_date' => '2027-01-01']);
        Http::fake(['whatsapp.test/*' => Http::response(['error' => 'Previous send requires manual verification'], 409)]);
        $this->artisan('contracts:send-reminders')->assertFailed();
        $this->artisan('contracts:send-reminders')->assertSuccessful();
        Http::assertSentCount(1);
        $this->assertDatabaseHas('contract_reminders', ['status' => 'uncertain', 'attempts' => 1]);
    }

    public function test_disabled_reminders_and_dry_run_send_nothing(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12', 'Asia/Amman'));
        Market::factory()->create(['lease_end_date' => '2027-01-01']);
        $setting = WhatsAppSetting::create(['id' => 1, 'enabled' => false]);
        $this->artisan('contracts:send-reminders')->assertSuccessful();
        $setting->update(['enabled' => true]);
        $this->artisan('contracts:send-reminders', ['--dry-run' => true])->assertSuccessful();
        Http::assertNothingSent();
        $this->assertDatabaseCount('contract_reminders', 0);
    }

    public function test_renewed_contract_gets_a_new_reminder(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12', 'Asia/Amman'));
        $market = Market::factory()->create(['lease_end_date' => '2027-01-01']);
        ContractReminder::create(['market_id' => $market->id, 'lease_end_date' => '2026-01-01', 'recipient' => '962770911991', 'status' => 'sent']);
        Http::fake(['whatsapp.test/*' => Http::response(['success' => true])]);
        $this->artisan('contracts:send-reminders')->assertSuccessful();
        Http::assertSentCount(1);
        $this->assertDatabaseCount('contract_reminders', 2);
    }

    public function test_settings_normalize_four_additional_numbers_and_preserve_primary_number(): void
    {
        $this->actingAs(User::factory()->create())->put(route('settings.whatsapp.update'), [
            'enabled' => 1, 'additional_numbers' => ['+962 79 000 0001', '+962790000002', '+962790000003', '+962790000004'],
        ])->assertSessionHasNoErrors()->assertRedirect(route('settings.edit'));
        $this->assertSame(['962770911991', '962790000001', '962790000002', '962790000003', '962790000004'], WhatsAppSetting::current()->recipients());
    }

    public function test_settings_reject_a_fifth_extra_number_duplicates_and_invalid_numbers(): void
    {
        $this->actingAs(User::factory()->create());
        $this->put(route('settings.whatsapp.update'), ['additional_numbers' => ['962790000001', '962790000002', '962790000003', '962790000004', '962790000005']])->assertSessionHasErrors(['additional_numbers']);
        $this->put(route('settings.whatsapp.update'), ['additional_numbers' => ['962790000001', '+962 79 000 0001']])->assertSessionHasErrors(['additional_numbers.0']);
        $this->put(route('settings.whatsapp.update'), ['additional_numbers' => ['invalid']])->assertSessionHasErrors(['additional_numbers.0']);
        $this->assertDatabaseCount('whats_app_settings', 0);
    }

    public function test_guests_cannot_access_whatsapp_or_change_recipients(): void
    {
        $this->get(route('settings.whatsapp.qr'))->assertRedirect(route('login'));
        $this->post(route('settings.whatsapp.connect'))->assertRedirect(route('login'));
        $this->put(route('settings.whatsapp.update'), ['additional_numbers' => ['962790000001']])->assertRedirect(route('login'));
        $this->assertDatabaseCount('whats_app_settings', 0);
        Http::assertNothingSent();
    }

    public function test_whatsapp_status_reports_unavailable_service_without_exposing_secrets(): void
    {
        Http::fake(['whatsapp.test/*' => Http::failedConnection()]);
        $this->actingAs(User::factory()->create())->getJson(route('settings.whatsapp.status'))->assertStatus(503)->assertJsonPath('status', 'unavailable')->assertDontSee('test-key');
        Http::assertSentCount(1);
    }

    public function test_authenticated_user_can_view_qr_and_control_the_connection(): void
    {
        Http::fake(['whatsapp.test/*' => Http::response(['success' => true, 'qr' => 'data:image/png;base64,test'])]);
        $this->actingAs(User::factory()->create())->getJson(route('settings.whatsapp.qr'))->assertOk()->assertJsonPath('qr', 'data:image/png;base64,test');
        $this->post(route('settings.whatsapp.connect'))->assertRedirect(route('settings.edit'))->assertSessionHasNoErrors();
        $this->post(route('settings.whatsapp.disconnect'))->assertRedirect(route('settings.edit'))->assertSessionHasNoErrors();
        Http::assertSentCount(3);
    }
}
