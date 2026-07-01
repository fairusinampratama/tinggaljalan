<?php

namespace App\Payments;

use App\Models\PaymentSetting;
use Illuminate\Support\Facades\Schema;

class PaymentSettingsService
{
    public function midtrans(): ?PaymentSetting
    {
        if (! Schema::hasTable('payment_settings')) {
            return null;
        }

        return PaymentSetting::query()->where('gateway', PaymentSetting::GATEWAY_MIDTRANS)->first();
    }

    public function midtransEnabled(): bool
    {
        return (bool) ($this->midtrans()?->is_enabled ?? true);
    }

    public function publicLabel(): string
    {
        return $this->midtrans()?->public_label ?: 'Secure Midtrans payment link';
    }

    public function bookingNote(): string
    {
        return $this->midtrans()?->booking_note ?: 'Payment is requested only after our team confirms availability.';
    }

    public function usdNote(): string
    {
        return $this->midtrans()?->usd_note ?: 'USD quotes are converted to IDR when payment is requested. Midtrans processes payments in IDR.';
    }

    /**
     * @return array<string, mixed>
     */
    public function publicPayload(): array
    {
        return [
            'provider' => 'midtrans',
            'publicLabel' => $this->publicLabel(),
            'bookingNote' => $this->bookingNote(),
            'usdNote' => $this->usdNote(),
        ];
    }

    public function midtransServerKey(): string
    {
        return (string) ($this->midtrans()?->secret_key ?: config('services.midtrans.server_key'));
    }

    public function midtransClientKey(): string
    {
        return (string) ($this->midtrans()?->public_key ?: config('services.midtrans.client_key'));
    }

    public function midtransIsProduction(): bool
    {
        $setting = $this->midtrans();

        if ($setting) {
            return $setting->mode === 'production';
        }

        return (bool) config('services.midtrans.is_production');
    }

    public function exchangeRateProvider(): string
    {
        return $this->midtrans()?->exchange_rate_provider ?: (string) config('services.exchange_rates.provider', 'frankfurter');
    }

    public function exchangeRateBufferPercent(): float
    {
        return (float) ($this->midtrans()?->exchange_rate_buffer_percent ?? config('services.exchange_rates.usd_idr_buffer_percent', 2));
    }

    public function exchangeRateCacheTtlHours(): int
    {
        return (int) ($this->midtrans()?->exchange_rate_cache_ttl_hours ?? config('services.exchange_rates.cache_ttl_hours', 12));
    }
}