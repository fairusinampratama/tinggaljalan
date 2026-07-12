<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Destination;
use App\Models\TourPackage;
use App\Payments\BookingPaymentService;
use App\Models\PaymentSetting;
use App\Payments\Doku\DokuClient;
use App\Payments\ExchangeRates\ExchangeRateClient;
use App\Payments\ExchangeRates\ExchangeRateService;
use App\Payments\ExchangeRates\FrankfurterExchangeRateClient;
use App\Payments\Midtrans\MidtransClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\Support\FakeExchangeRateClient;
use Tests\Support\FakeDokuClient;
use Tests\Support\FakeMidtransClient;
use Tests\TestCase;

class BookingPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private FakeMidtransClient $midtrans;

    private FakeDokuClient $doku;

    private FakeExchangeRateClient $exchangeRates;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config([
            'services.midtrans.server_key' => 'server-key',
            'services.exchange_rates.provider' => 'frankfurter',
            'services.exchange_rates.usd_idr_buffer_percent' => 2,
            'services.exchange_rates.cache_ttl_hours' => 12,
        ]);
        $this->midtrans = new FakeMidtransClient();
        $this->doku = new FakeDokuClient();
        $this->exchangeRates = new FakeExchangeRateClient();
        $this->app->instance(MidtransClient::class, $this->midtrans);
        $this->app->instance(DokuClient::class, $this->doku);
        $this->app->instance(ExchangeRateClient::class, $this->exchangeRates);
    }

    public function test_idr_payment_request_uses_booking_total_without_exchange_rate(): void
    {
        $booking = $this->booking(['currency' => 'IDR', 'total' => 750000]);

        $payment = app(BookingPaymentService::class)->createPaymentRequest($booking);

        $this->assertSame('IDR', $payment->quote_currency);
        $this->assertSame(750000, $payment->quote_amount);
        $this->assertNull($payment->exchange_rate);
        $this->assertNull($payment->exchange_rate_snapshot);
        $this->assertSame(750000, $payment->charge_amount);
        $this->assertSame(0, $this->exchangeRates->calls);
        $this->assertSame('fake-snap-token', $payment->snap_token);
        $this->assertSame(750000, $this->midtrans->createdPayloads[0]['transaction_details']['gross_amount']);
    }


    public function test_doku_payment_request_uses_checkout_payment_url(): void
    {
        PaymentSetting::midtrans()->update(['is_enabled' => false]);
        PaymentSetting::doku()->update([
            'is_enabled' => true,
            'public_key' => 'doku-client-id',
            'secret_key' => 'doku-secret-key',
        ]);
        $booking = $this->booking(['currency' => 'IDR', 'total' => 880000]);

        $payment = app(BookingPaymentService::class)->createPaymentRequest($booking);

        $this->assertSame('doku', $payment->provider);
        $this->assertSame('fake-doku-token', $payment->snap_token);
        $this->assertSame('https://sandbox.doku.com/checkout/link/fake-doku-token', $payment->snap_url);
        $this->assertSame('fake-doku-token', $payment->doku_payment_token);
        $this->assertSame(880000, $this->doku->createdPayloads[0]['order']['amount']);
        $this->assertSame($payment->order_id, $this->doku->createdPayloads[0]['order']['invoice_number']);
        $this->assertSame($payment->order_id, $this->doku->requestIds[0]);
    }

    public function test_doku_statuses_map_to_local_payment_states(): void
    {
        $payment = BookingPayment::create([
            'booking_id' => $this->booking()->id,
            'provider' => 'doku',
            'order_id' => 'TJ1PABCDEFGH',
            'public_token' => 'doku-public-token',
            'quote_currency' => 'IDR',
            'quote_amount' => 500000,
            'charge_currency' => 'IDR',
            'charge_amount' => 500000,
            'status' => 'pending',
        ]);
        $service = app(BookingPaymentService::class);

        $paid = $service->applyDokuStatus($payment, ['transaction' => ['status' => 'SUCCESS']]);
        $this->assertSame('paid', $paid->status);
        $this->assertNotNull($paid->paid_at);

        $expired = $service->applyDokuStatus($paid, ['transaction' => ['status' => 'EXPIRED']]);
        $this->assertSame('expired', $expired->status);
        $this->assertNotNull($expired->expired_at);
    }

    public function test_frankfurter_client_parses_usd_to_idr_rate(): void
    {
        Http::fake([
            'api.frankfurter.dev/*' => Http::response([
                'amount' => 1,
                'base' => 'USD',
                'date' => '2026-06-25',
                'rates' => ['IDR' => 16500.25],
            ]),
        ]);

        $rate = (new FrankfurterExchangeRateClient())->usdToIdr();

        $this->assertSame('frankfurter', $rate['source']);
        $this->assertSame(16500.25, $rate['raw_rate']);
    }

    public function test_exchange_rate_service_applies_buffer_and_rounds_final_rate(): void
    {
        $this->exchangeRates->payload['raw_rate'] = 16500.25;

        $quote = app(ExchangeRateService::class)->usdToIdr();

        $this->assertSame(16500.25, $quote->rawRate);
        $this->assertSame(2.0, $quote->bufferPercent);
        $this->assertSame(16831, $quote->finalRate);
        $this->assertFalse($quote->manualOverride);
    }

    public function test_exchange_rate_service_reuses_cached_rate_when_provider_fails(): void
    {
        $service = app(ExchangeRateService::class);
        $quote = $service->usdToIdr();
        $this->assertSame(16830, $quote->finalRate);

        $this->exchangeRates->shouldFail = true;
        $cached = $service->usdToIdr();

        $this->assertSame(16830, $cached->finalRate);
        $this->assertTrue($cached->fromCache);
    }

    public function test_usd_payment_request_auto_fetches_rate_and_charges_idr(): void
    {
        $booking = $this->booking(['currency' => 'USD', 'total' => 120]);

        $payment = app(BookingPaymentService::class)->createPaymentRequest($booking);

        $this->assertSame('USD', $payment->quote_currency);
        $this->assertSame(120, $payment->quote_amount);
        $this->assertSame(16830, $payment->exchange_rate);
        $this->assertSame(2019600, $payment->charge_amount);
        $this->assertSame(2019600, $this->midtrans->createdPayloads[0]['transaction_details']['gross_amount']);
        $this->assertSame('frankfurter', $payment->exchange_rate_snapshot['source']);
        $this->assertEquals(16500.0, $payment->exchange_rate_snapshot['raw_rate']);
        $this->assertEquals(2.0, $payment->exchange_rate_snapshot['buffer_percent']);
        $this->assertSame(16830, $payment->exchange_rate_snapshot['final_rate']);
        $this->assertFalse($payment->exchange_rate_snapshot['manual_override']);
    }

    public function test_manual_exchange_rate_override_bypasses_provider_and_records_snapshot(): void
    {
        $booking = $this->booking(['currency' => 'USD', 'total' => 120]);

        $payment = app(BookingPaymentService::class)->createPaymentRequest($booking, 16500);

        $this->assertSame(0, $this->exchangeRates->calls);
        $this->assertSame(16500, $payment->exchange_rate);
        $this->assertSame(1980000, $payment->charge_amount);
        $this->assertSame('manual', $payment->exchange_rate_snapshot['source']);
        $this->assertSame(0, $payment->exchange_rate_snapshot['buffer_percent']);
        $this->assertTrue($payment->exchange_rate_snapshot['manual_override']);
    }

    public function test_usd_payment_request_requires_manual_rate_when_provider_and_cache_fail(): void
    {
        $this->exchangeRates->shouldFail = true;
        $booking = $this->booking(['currency' => 'USD', 'total' => 120]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not fetch USD to IDR rate');

        app(BookingPaymentService::class)->createPaymentRequest($booking);
    }

    public function test_midtrans_statuses_map_to_local_payment_states(): void
    {
        $payment = BookingPayment::create([
            'booking_id' => $this->booking()->id,
            'provider' => 'midtrans',
            'order_id' => 'TJ-TEST-PAY',
            'public_token' => 'public-token',
            'quote_currency' => 'IDR',
            'quote_amount' => 500000,
            'charge_currency' => 'IDR',
            'charge_amount' => 500000,
            'status' => 'pending',
        ]);
        $service = app(BookingPaymentService::class);

        $paid = $service->applyMidtransStatus($payment, ['transaction_status' => 'settlement']);
        $this->assertSame('paid', $paid->status);
        $this->assertNotNull($paid->paid_at);

        $expired = $service->applyMidtransStatus($paid, ['transaction_status' => 'expire']);
        $this->assertSame('expired', $expired->status);
        $this->assertNotNull($expired->expired_at);
    }

    public function test_signature_verification_rejects_invalid_webhook_payload(): void
    {
        $service = app(BookingPaymentService::class);

        $this->assertFalse($service->verifySignature([
            'order_id' => 'TJ-TEST-PAY',
            'status_code' => '200',
            'gross_amount' => '500000.00',
            'signature_key' => 'wrong',
        ]));
    }

    private function booking(array $overrides = []): Booking
    {
        $destination = Destination::create([
            'slug' => 'bromo',
            'name' => 'Bromo',
        ]);
        $package = TourPackage::create([
            'destination_id' => $destination->id,
            'slug' => 'bromo-sunrise',
            'title' => ['us' => 'Bromo Sunrise'],
            'base_price_idr' => 500000,
            'base_price_usd' => 35,
            'is_active' => true,
        ]);

        return Booking::create(array_merge([
            'booking_code' => 'TJ-TEST-'.strtoupper(fake()->bothify('????')),
            'tour_package_id' => $package->id,
            'destination_id' => $destination->id,
            'name' => 'Payment Test',
            'email' => 'payment@example.test',
            'whatsapp' => '+6281234567890',
            'travel_date' => now()->addWeeks(3)->toDateString(),
            'pax' => 2,
            'traveler_type' => 'international',
            'currency' => 'IDR',
            'subtotal' => 500000,
            'discount_total' => 0,
            'total' => 500000,
            'status' => 'confirmed',
        ], $overrides));
    }
}