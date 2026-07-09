<?php

namespace Tests\Unit;

use App\Models\PaymentSetting;
use App\Payments\Midtrans\HttpMidtransClient;
use App\Payments\PaymentSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentSettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_settings_fall_back_to_env_config_when_database_row_is_missing(): void
    {
        PaymentSetting::query()->delete();

        config([
            'services.midtrans.server_key' => 'env-server-key',
            'services.midtrans.client_key' => 'env-client-key',
            'services.midtrans.is_production' => true,
            'services.exchange_rates.usd_idr_buffer_percent' => 3,
            'services.exchange_rates.cache_ttl_hours' => 24,
        ]);

        $settings = app(PaymentSettingsService::class);

        $this->assertSame('env-server-key', $settings->midtransServerKey());
        $this->assertSame('env-client-key', $settings->midtransClientKey());
        $this->assertTrue($settings->midtransIsProduction());
        $this->assertSame(3.0, $settings->exchangeRateBufferPercent());
        $this->assertSame(24, $settings->exchangeRateCacheTtlHours());
    }

    public function test_payment_settings_use_encrypted_database_values_and_public_payload_hides_secrets(): void
    {
        $setting = PaymentSetting::midtrans();
        $setting->update([
            'mode' => 'sandbox',
            'public_key' => 'db-client-key',
            'secret_key' => 'db-server-key',
            'public_label' => 'Configured Midtrans link',
            'booking_note' => 'Configured booking note.',
            'usd_note' => 'Configured USD note.',
            'exchange_rate_buffer_percent' => 4,
            'exchange_rate_cache_ttl_hours' => 6,
        ]);
        $raw = $setting->refresh()->getRawOriginal();
        $settings = app(PaymentSettingsService::class);

        $this->assertNotSame('db-client-key', $raw['public_key']);
        $this->assertNotSame('db-server-key', $raw['secret_key']);
        $this->assertSame('db-client-key', $settings->midtransClientKey());
        $this->assertSame('db-server-key', $settings->midtransServerKey());
        $this->assertFalse($settings->midtransIsProduction());
        $this->assertSame(4.0, $settings->exchangeRateBufferPercent());
        $this->assertSame(6, $settings->exchangeRateCacheTtlHours());
        $this->assertSame([
            'provider' => 'midtrans',
            'publicLabel' => 'Configured Midtrans link',
            'bookingNote' => 'Configured booking note.',
            'usdNote' => 'Configured USD note.',
            'manualBankAccounts' => [],
        ], $settings->publicPayload());
    }

    public function test_midtrans_client_uses_database_server_key_when_configured(): void
    {
        PaymentSetting::midtrans()->update([
            'secret_key' => 'db-server-key',
        ]);
        Http::fake([
            'app.sandbox.midtrans.com/*' => Http::response(['token' => 'token', 'redirect_url' => 'https://example.test/pay']),
        ]);

        app(HttpMidtransClient::class)->createSnapTransaction(['transaction_details' => ['order_id' => 'TJ-TEST', 'gross_amount' => 1000]]);

        Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Basic '.base64_encode('db-server-key:')));
    }
}