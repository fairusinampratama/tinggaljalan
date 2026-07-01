<?php

namespace App\Payments\Midtrans;

use App\Payments\PaymentSettingsService;
use Illuminate\Support\Facades\Http;

class HttpMidtransClient implements MidtransClient
{
    public function __construct(private readonly PaymentSettingsService $settings)
    {
    }

    public function createSnapTransaction(array $payload): array
    {
        return Http::withBasicAuth($this->serverKey(), '')
            ->acceptJson()
            ->post($this->snapBaseUrl().'/snap/v1/transactions', $payload)
            ->throw()
            ->json();
    }

    public function status(string $orderId): array
    {
        return Http::withBasicAuth($this->serverKey(), '')
            ->acceptJson()
            ->get($this->apiBaseUrl()."/v2/{$orderId}/status")
            ->throw()
            ->json();
    }

    public function cancel(string $orderId): array
    {
        return Http::withBasicAuth($this->serverKey(), '')
            ->acceptJson()
            ->post($this->apiBaseUrl()."/v2/{$orderId}/cancel")
            ->throw()
            ->json();
    }

    private function serverKey(): string
    {
        return $this->settings->midtransServerKey();
    }

    private function snapBaseUrl(): string
    {
        return $this->settings->midtransIsProduction()
            ? 'https://app.midtrans.com'
            : 'https://app.sandbox.midtrans.com';
    }

    private function apiBaseUrl(): string
    {
        return $this->settings->midtransIsProduction()
            ? 'https://api.midtrans.com'
            : 'https://api.sandbox.midtrans.com';
    }
}