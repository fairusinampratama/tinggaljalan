<?php

namespace Tests\Unit;

use App\Gateways\Email\EmailGatewayService;
use App\Gateways\WhatsApp\PaymentReceiptWhatsAppMessage;
use App\Gateways\WhatsApp\PaymentRequestWhatsAppMessage;
use App\Gateways\WhatsApp\WhatsAppGatewayService;
use App\Gateways\WhatsApp\WhatspieClient;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Destination;
use App\Models\EmailGatewaySetting;
use App\Models\TourPackage;
use App\Models\WhatsappGatewaySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\Support\FakeWhatspieClient;
use Tests\TestCase;

class GatewaySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_gateway_setting_secrets_are_encrypted(): void
    {
        $email = EmailGatewaySetting::current();
        $email->update(['password' => 'smtp-secret']);

        $whatsapp = WhatsappGatewaySetting::current();
        $whatsapp->update(['api_token' => 'wa-secret']);

        $this->assertNotSame('smtp-secret', $email->refresh()->getRawOriginal('password'));
        $this->assertSame('smtp-secret', $email->password);
        $this->assertNotSame('wa-secret', $whatsapp->refresh()->getRawOriginal('api_token'));
        $this->assertSame('wa-secret', $whatsapp->api_token);
    }

    public function test_email_gateway_validates_smtp_config(): void
    {
        EmailGatewaySetting::current()->update([
            'provider' => EmailGatewaySetting::PROVIDER_SMTP,
            'is_enabled' => true,
            'host' => null,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Email gateway host is required for SMTP.');

        app(EmailGatewayService::class)->sendTest('qa@example.test');
    }

    public function test_payment_request_whatsapp_message_contains_booking_payment_details(): void
    {
        $payment = $this->payment();
        $message = app(PaymentRequestWhatsAppMessage::class)->text($payment);

        $this->assertStringContainsString($payment->booking->booking_code, $message);
        $this->assertStringContainsString('*Bromo Sunrise*', $message);
        $this->assertStringContainsString('Hello Gateway Test', $message);
        $this->assertStringContainsString('Amount to pay: Rp700.000', $message);
        $this->assertStringContainsString('Pay before:', $message);
        $this->assertStringContainsString('WIB', $message);
        $this->assertStringContainsString('Never share your OTP', $message);
        $this->assertStringContainsString("\n*Pay securely with Midtrans*\n".route('checkout.payment.show', $payment->public_token)."\n", $message);
        $this->assertTrue(mb_check_encoding($message, 'UTF-8'));
    }

    public function test_usd_payment_request_explains_the_idr_midtrans_charge(): void
    {
        $payment = $this->payment();
        $payment->update([
            'quote_currency' => 'USD',
            'quote_amount' => 69,
            'exchange_rate' => 18247,
            'charge_amount' => 1259043,
        ]);

        $message = app(PaymentRequestWhatsAppMessage::class)->text($payment->refresh());

        $this->assertStringContainsString('Original quote: $69', $message);
        $this->assertStringContainsString('Midtrans charge (IDR): Rp1.259.043', $message);
        $this->assertStringContainsString('Rate used: USD 1 = Rp18.247', $message);
    }
    public function test_whatspie_gateway_marks_payment_sent(): void
    {
        $fake = new FakeWhatspieClient();
        $this->app->instance(WhatspieClient::class, $fake);
        WhatsappGatewaySetting::current()->update([
            'provider' => WhatsappGatewaySetting::PROVIDER_WHATSPIE,
            'is_enabled' => true,
            'api_base_url' => 'https://api.whatspie.test',
            'api_token' => 'token',
            'session_id' => 'session-1',
        ]);
        $payment = $this->payment();

        $result = app(WhatsAppGatewayService::class)->sendPaymentRequest($payment);

        $payment->refresh();
        $this->assertTrue($result->sent);
        $this->assertNotNull($payment->whatsapp_sent_at);
        $this->assertSame('45136466', $payment->whatsapp_provider_message_id);
        $this->assertNull($payment->whatsapp_failed_at);
        $this->assertSame('628111111111', $fake->sent[0]['to']);
    }

    public function test_whatspie_failure_marks_failed_and_uses_manual_fallback_without_mutating_payment_snapshot(): void
    {
        $fake = new FakeWhatspieClient();
        $fake->exception = new \RuntimeException('Whatspie session disconnected');
        $this->app->instance(WhatspieClient::class, $fake);
        WhatsappGatewaySetting::current()->update([
            'provider' => WhatsappGatewaySetting::PROVIDER_WHATSPIE,
            'is_enabled' => true,
            'api_base_url' => 'https://api.whatspie.test',
            'api_token' => 'token',
            'session_id' => 'session-1',
            'manual_fallback_enabled' => true,
        ]);
        $payment = $this->payment();
        $original = $payment->only(['quote_amount', 'charge_amount', 'status', 'sent_at', 'paid_at', 'cancelled_at']);

        $result = app(WhatsAppGatewayService::class)->sendPaymentRequest($payment);

        $payment->refresh();
        $this->assertTrue($result->manualFallback);
        $this->assertStringStartsWith('https://wa.me/', $result->redirectUrl);
        $this->assertNotNull($payment->whatsapp_failed_at);
        $this->assertNotNull($payment->whatsapp_opened_at);
        $this->assertSame('Whatspie session disconnected', $payment->whatsapp_error);
        $this->assertSame($original['quote_amount'], $payment->quote_amount);
        $this->assertSame($original['charge_amount'], $payment->charge_amount);
        $this->assertSame($original['status'], $payment->status);
        $this->assertSame($original['sent_at'], $payment->sent_at);
        $this->assertSame($original['paid_at'], $payment->paid_at);
        $this->assertSame($original['cancelled_at'], $payment->cancelled_at);
    }

    public function test_whatspie_failure_without_manual_fallback_records_failure_only(): void
    {
        $fake = new FakeWhatspieClient();
        $fake->exception = new \RuntimeException('Whatspie session disconnected');
        $this->app->instance(WhatspieClient::class, $fake);
        WhatsappGatewaySetting::current()->update([
            'provider' => WhatsappGatewaySetting::PROVIDER_WHATSPIE,
            'is_enabled' => true,
            'api_base_url' => 'https://api.whatspie.test',
            'api_token' => 'token',
            'session_id' => 'session-1',
            'manual_fallback_enabled' => false,
        ]);
        $payment = $this->payment();

        $result = app(WhatsAppGatewayService::class)->sendPaymentRequest($payment);

        $payment->refresh();
        $this->assertFalse($result->sent);
        $this->assertFalse($result->manualFallback);
        $this->assertSame('Whatspie session disconnected', $result->error);
        $this->assertNotNull($payment->whatsapp_failed_at);
        $this->assertNull($payment->whatsapp_opened_at);
        $this->assertSame('Whatspie session disconnected', $payment->whatsapp_error);
    }

    public function test_force_manual_payment_whatsapp_uses_fallback_even_when_whatspie_is_enabled(): void
    {
        $fake = new FakeWhatspieClient();
        $this->app->instance(WhatspieClient::class, $fake);
        WhatsappGatewaySetting::current()->update([
            'provider' => WhatsappGatewaySetting::PROVIDER_WHATSPIE,
            'is_enabled' => true,
            'api_base_url' => 'https://api.whatspie.test',
            'api_token' => 'token',
            'session_id' => 'session-1',
            'manual_fallback_enabled' => true,
        ]);
        $payment = $this->payment();

        $result = app(WhatsAppGatewayService::class)->sendPaymentRequest($payment, forceManual: true);

        $payment->refresh();
        $this->assertTrue($result->manualFallback);
        $this->assertStringStartsWith('https://wa.me/', $result->redirectUrl);
        $this->assertNotNull($payment->whatsapp_opened_at);
        $this->assertNull($payment->whatsapp_sent_at);
        $this->assertSame([], $fake->sent);
    }
    public function test_payment_whatsapp_messages_follow_booking_language(): void
    {
        $payment = $this->payment();
        $payment->booking->update(['communication_language' => 'id']);

        $request = app(PaymentRequestWhatsAppMessage::class)->text($payment->fresh('booking'));
        $this->assertStringContainsString('Halo Gateway Test', $request);
        $this->assertStringContainsString('Jumlah yang harus dibayar', $request);

        $payment->booking->update(['communication_language' => 'cn']);
        $payment->update(['status' => 'paid', 'paid_at' => now()]);

        $receipt = app(PaymentReceiptWhatsAppMessage::class)->text($payment->fresh('booking'));
        $this->assertStringContainsString('您好，Gateway Test', $receipt);
        $this->assertStringContainsString('已收到付款', $receipt);
        $this->assertTrue(mb_check_encoding($receipt, 'UTF-8'));
    }
    public function test_paid_payment_cannot_send_payment_request_whatsapp(): void
    {
        $fake = new FakeWhatspieClient();
        $this->app->instance(WhatspieClient::class, $fake);
        $payment = $this->payment();
        $payment->update(['status' => 'paid', 'paid_at' => now()]);

        $result = app(WhatsAppGatewayService::class)->sendPaymentRequest($payment->refresh());

        $this->assertFalse($result->sent);
        $this->assertFalse($result->manualFallback);
        $this->assertStringContainsString('only be sent while payment is awaiting payment', $result->error);
        $this->assertSame([], $fake->sent);
        $this->assertNull($payment->refresh()->whatsapp_sent_at);
    }
    private function payment(): BookingPayment
    {
        $destination = Destination::firstOrCreate(['slug' => 'bromo'], ['name' => 'Bromo']);
        $package = TourPackage::firstOrCreate(['slug' => 'bromo-sunrise'], [
            'destination_id' => $destination->id,
            'title' => ['us' => 'Bromo Sunrise'],
            'base_price_idr' => 700000,
            'base_price_usd' => 45,
            'is_active' => true,
        ]);
        $booking = Booking::create([
            'booking_code' => 'TJ-GATEWAY-'.fake()->unique()->bothify('????'),
            'tour_package_id' => $package->id,
            'destination_id' => $package->destination_id,
            'name' => 'Gateway Test',
            'email' => 'gateway@example.test',
            'whatsapp' => '+628111111111',
            'travel_date' => now()->addWeeks(2)->toDateString(),
            'pax' => 2,
            'traveler_type' => 'local',
            'currency' => 'IDR',
            'subtotal' => 700000,
            'discount_total' => 0,
            'total' => 700000,
            'status' => 'confirmed',
        ]);

        return BookingPayment::create([
            'booking_id' => $booking->id,
            'provider' => 'midtrans',
            'order_id' => 'TJ-GATEWAY-PAY-'.fake()->unique()->bothify('????'),
            'public_token' => str()->random(40),
            'quote_currency' => 'IDR',
            'quote_amount' => 700000,
            'charge_currency' => 'IDR',
            'charge_amount' => 700000,
            'status' => 'pending',
            'snap_token' => 'fake-token',
            'snap_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/fake-token',
            'expires_at' => now()->addDay(),
        ]);
    }
}