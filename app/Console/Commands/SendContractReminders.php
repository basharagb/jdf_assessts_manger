<?php

namespace App\Console\Commands;

use App\Models\ContractReminder;
use App\Models\Market;
use App\Models\WhatsAppSetting;
use App\WhatsAppWebClient;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Throwable;

class SendContractReminders extends Command
{
    protected $signature = 'contracts:send-reminders {--dry-run : List eligible contracts without sending messages}';
    protected $description = 'Send one WhatsApp reminder per recipient within 90 days of contract expiry';

    public function handle(WhatsAppWebClient $client): int
    {
        $settings = WhatsAppSetting::current();
        if (! $settings->enabled) {
            $this->info('Contract reminders are disabled.');
            return self::SUCCESS;
        }
        $today = Carbon::today('Asia/Amman');
        $markets = Market::query()->with('group')->where('is_active', true)
            ->whereBetween('lease_end_date', [$today->toDateString(), $today->copy()->addDays(90)->toDateString()]);
        if ($this->option('dry-run')) {
            $this->info('Eligible contracts: '.$markets->count().'; recipients: '.count($settings->recipients()));
            return self::SUCCESS;
        }
        $lock = Cache::lock('contracts:send-reminders', 3600);
        if (! $lock->get()) {
            $this->warn('Another reminder run is active.');
            return self::SUCCESS;
        }
        $failed = false;
        try {
            foreach ($markets->lazyById(100) as $market) {
                foreach ($settings->recipients() as $recipient) {
                    $reminder = ContractReminder::firstOrCreate([
                        'market_id' => $market->id,
                        'lease_end_date' => $market->lease_end_date->toDateString(),
                        'recipient' => $recipient,
                    ]);
                    if (in_array($reminder->status, ['sent', 'uncertain'], true)) {
                        continue;
                    }
                    $reminder->increment('attempts');
                    try {
                        $message = "الشؤون الادارية- توثيق وترخيص ممتلكات\nتنبيه قرب انتهاء عقد الإيجار\nالسوق: ".$market->name.
                            "\nالمنطقة: ".$market->group->name."\nالجهة المؤجرة: ".$market->lessor.
                            "\nبداية العقد: ".$market->lease_start_date->format('d/m/Y')."\nنهاية العقد: ".$market->lease_end_date->format('d/m/Y').
                            "\nالأيام المتبقية: ".(int) $today->diffInDays($market->lease_end_date).
                            "\nالإيجار السنوي الأساسي: ".number_format((float) $market->annual_rent, 2).' د.أ'.
                            "\nيرجى متابعة التجديد والترخيص قبل انتهاء العقد.";
                        $result = $client->send($recipient, $message, 'contract-'.$reminder->id);
                        $reminder->update(['status' => 'sent', 'sent_at' => now(), 'message_id' => $result['messageId'] ?? null, 'last_error' => null]);
                    } catch (Throwable $exception) {
                        $failed = true;
                        $uncertain = $exception instanceof RequestException && $exception->response->status() === 409;
                        $reminder->update(['status' => $uncertain ? 'uncertain' : 'failed', 'last_error' => mb_substr($exception->getMessage(), 0, 2000)]);
                        report($exception);
                        $this->error('Reminder '.$reminder->id.' failed; see the settings log.');
                    }
                }
            }
        } finally {
            $lock->release();
        }
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
