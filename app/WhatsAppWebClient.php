<?php

namespace App;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WhatsAppWebClient
{
    private function request(): PendingRequest
    {
        $key = config('services.whatsapp.api_key');
        if (! is_string($key) || $key === '') {
            throw new RuntimeException('WhatsApp service API key is not configured.');
        }
        return Http::acceptJson()->connectTimeout(3)->timeout(30)
            ->baseUrl(rtrim(config('services.whatsapp.url'), '/'))
            ->withHeaders(['x-api-key' => $key]);
    }

    /** @return array<string, mixed> */
    public function status(): array
    {
        return $this->request()->get('/whatsapp/status')->throw()->json();
    }

    /** @return array<string, mixed> */
    public function qr(): array
    {
        return $this->request()->get('/whatsapp/qr')->throw()->json();
    }

    public function connect(): void
    {
        $this->request()->post('/whatsapp/connect')->throw();
    }

    public function disconnect(): void
    {
        $this->request()->post('/whatsapp/disconnect')->throw();
    }

    /** @return array<string, mixed> */
    public function send(string $recipient, string $message, string $idempotencyKey): array
    {
        $response = $this->request()->withHeaders(['Idempotency-Key' => $idempotencyKey])
            ->post('/whatsapp/send', ['to' => $recipient, 'message' => $message])->throw();
        if ($response->json('success') !== true) {
            throw new RuntimeException('WhatsApp service did not confirm sending.');
        }
        return $response->json();
    }
}
