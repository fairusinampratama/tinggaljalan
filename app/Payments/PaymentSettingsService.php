<?php

namespace App\Payments;

use App\Models\PaymentSetting;
use Illuminate\Support\Facades\Schema;

class PaymentSettingsService
{
    private array $settingsCache = [];
    public function midtrans(): ?PaymentSetting
    {
        if (! Schema::hasTable('payment_settings')) {
            return null;
        }

        return PaymentSetting::query()->where('gateway', PaymentSetting::GATEWAY_MIDTRANS)->first();
    }

    public function active(): ?PaymentSetting
    {
        if (! Schema::hasTable('payment_settings')) {
            return null;
        }

        return PaymentSetting::query()->where('is_enabled', true)->first() ?? $this->midtrans();
    }

    public function isManualActive(): bool
    {
        return $this->active()?->gateway === PaymentSetting::GATEWAY_MANUAL;
    }

    public function midtransEnabled(): bool
    {
        return $this->active()?->gateway === PaymentSetting::GATEWAY_MIDTRANS;
    }

    private function getSetting(?string $provider = null): ?PaymentSetting
    {
        if ($provider === null) {
            return $this->active();
        }

        if (! array_key_exists($provider, $this->settingsCache)) {
            $this->settingsCache[$provider] = PaymentSetting::query()->where('gateway', $provider)->first();
        }

        return $this->settingsCache[$provider];
    }

    public function publicLabel(?string $provider = null): string
    {
        return $this->getSetting($provider)?->public_label ?: 'Secure Midtrans payment link';
    }

    public function bookingNote(?string $provider = null): string
    {
        return $this->getSetting($provider)?->booking_note ?: 'Payment is requested only after our team confirms availability.';
    }

    public function usdNote(?string $provider = null): string
    {
        return $this->getSetting($provider)?->usd_note ?: 'USD quotes are converted to IDR when payment is requested.';
    }

    public function manualBankAccounts(?string $provider = null): array
    {
        return $this->getSetting($provider)?->manual_bank_accounts ?: [];
    }

    /**
     * @return array<string, mixed>
     */
    public function publicPayload(?string $provider = null): array
    {
        return [
            'provider' => $this->getSetting($provider)?->gateway ?: 'midtrans',
            'publicLabel' => $this->publicLabel($provider),
            'bookingNote' => $this->bookingNote($provider),
            'usdNote' => $this->usdNote($provider),
            'manualBankAccounts' => $this->manualBankAccounts($provider),
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
        return $this->active()?->exchange_rate_provider ?: (string) config('services.exchange_rates.provider', 'frankfurter');
    }

    public function exchangeRateBufferPercent(): float
    {
        return (float) ($this->active()?->exchange_rate_buffer_percent ?? config('services.exchange_rates.usd_idr_buffer_percent', 2));
    }

    public function exchangeRateCacheTtlHours(): int
    {
        return (int) ($this->active()?->exchange_rate_cache_ttl_hours ?? config('services.exchange_rates.cache_ttl_hours', 12));
    }
}