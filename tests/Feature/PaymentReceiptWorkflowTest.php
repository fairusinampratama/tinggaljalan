<?php

namespace Tests\Feature;

use App\Gateways\Email\EmailGatewayService;
use App\Gateways\WhatsApp\PaymentReceiptWhatsAppMessage;
use App\Gateways\WhatsApp\WhatspieClient;
use App\Mail\BookingPaymentReceiptMail;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Destination;
use App\Models\TourPackage;
use App\Models\WhatsappGatewaySetting;
use App\Payments\BookingPaymentService;
use App\Payments\PaymentReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\Support\FakeWhatspieClient;
use Tests\TestCase;

class PaymentReceiptWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_transition_sends_email_and_whatsapp_exactly_once(): void
    {
        Mail::fake();
        $fake = $this->enableWhatspie();
        $payment = $this->payment();
        $snapshot = $payment->only(['quote_amount', 'charge_amount', 'sent_at']);
        $service = app(BookingPaymentService::class);

        $service->applyMidtransStatus($payment, [
            'transaction_status' => 'settlement',
            'transaction_id' => 'midtrans-receipt-1',
            'payment_type' => 'credit_card',
        ]);
        $service->applyMidtransStatus($payment->refresh(), [
            'transaction_status' => 'settlement',
            'transaction_id' => 'midtrans-receipt-1',
            'payment_type' => 'credit_card',
        ]);

        Mail::assertSent(BookingPaymentReceiptMail::class, 1);
        $this->assertCount(1, $fake->sent);
        $payment->refresh();
        $this->assertSame('paid', $payment->status);
        $this->assertNotNull($payment->receipt_notifications_attempted_at);
        $this->assertNotNull($payment->receipt_email_sent_at);
        $this->assertNotNull($payment->receipt_whatsapp_sent_at);
        $this->assertSame('confirmed', $payment->booking->refresh()->status);
        $this->assertSame($snapshot['quote_amount'], $payment->quote_amount);
        $this->assertSame($snapshot['charge_amount'], $payment->charge_amount);
        $this->assertEquals($snapshot['sent_at'], $payment->sent_at);
    }

    public function test_accepted_capture_also_sends_receipt(): void
    {
        Mail::fake();
        $this->enableWhatspie();

        $payment = app(BookingPaymentService::class)->applyMidtransStatus($this->payment(), [
            'transaction_status' => 'capture',
            'fraud_status' => 'accept',
        ]);

        $this->assertSame('paid', $payment->status);
        $this->assertNotNull($payment->receipt_email_sent_at);
        $this->assertNotNull($payment->receipt_whatsapp_sent_at);
    }

    public function test_email_failure_does_not_block_whatsapp_or_paid_state(): void
    {
        $fake = $this->enableWhatspie();
        $email = Mockery::mock(EmailGatewayService::class);
        $email->shouldReceive('sendReceipt')->once()->andThrow(new \RuntimeException('SMTP unavailable'));
        $this->app->instance(EmailGatewayService::class, $email);

        $payment = $this->payment(['status' => 'paid', 'paid_at' => now()]);
        app(PaymentReceiptService::class)->sendAutomatically($payment);

        $payment->refresh();
        $this->assertSame('paid', $payment->status);
        $this->assertNotNull($payment->receipt_email_failed_at);
        $this->assertSame('SMTP unavailable', $payment->receipt_email_error);
        $this->assertNotNull($payment->receipt_whatsapp_sent_at);
        $this->assertCount(1, $fake->sent);
    }

    public function test_whatsapp_failure_does_not_block_email_or_paid_state(): void
    {
        Mail::fake();
        $fake = $this->enableWhatspie();
        $fake->exception = new \RuntimeException('Whatspie disconnected');

        $payment = $this->payment(['status' => 'paid', 'paid_at' => now()]);
        app(PaymentReceiptService::class)->sendAutomatically($payment);

        Mail::assertSent(BookingPaymentReceiptMail::class, 1);
        $payment->refresh();
        $this->assertSame('paid', $payment->status);
        $this->assertNotNull($payment->receipt_email_sent_at);
        $this->assertNotNull($payment->receipt_whatsapp_failed_at);
        $this->assertSame('Whatspie disconnected', $payment->receipt_whatsapp_error);
    }

    public function test_manual_gateway_and_missing_contacts_are_tracked_without_changing_payment(): void
    {
        Mail::fake();
        WhatsappGatewaySetting::current()->update(['provider' => 'manual', 'is_enabled' => true]);
        $manual = $this->payment(['status' => 'paid', 'paid_at' => now()]);

        app(PaymentReceiptService::class)->sendAutomatically($manual);

        $manual->refresh();
        $this->assertNotNull($manual->receipt_email_sent_at);
        $this->assertNotNull($manual->receipt_whatsapp_failed_at);
        $this->assertNull($manual->receipt_whatsapp_opened_at);
        $this->assertStringContainsString('manual', strtolower($manual->receipt_whatsapp_error));

        $missing = $this->payment([
            'booking_id' => $this->booking(['email' => null, 'whatsapp' => null])->id,
            'status' => 'paid',
            'paid_at' => now(),
        ]);
        app(PaymentReceiptService::class)->sendAutomatically($missing);

        $missing->refresh();
        $this->assertSame('paid', $missing->status);
        $this->assertNotNull($missing->receipt_email_failed_at);
        $this->assertNotNull($missing->receipt_whatsapp_failed_at);
    }

    public function test_manual_retry_sends_only_the_requested_channel(): void
    {
        Mail::fake();
        $fake = $this->enableWhatspie();
        $payment = $this->payment([
            'status' => 'paid',
            'paid_at' => now(),
            'receipt_notifications_attempted_at' => now(),
            'receipt_email_failed_at' => now(),
            'receipt_email_error' => 'Previous failure',
        ]);

        $result = app(PaymentReceiptService::class)->sendEmail($payment);

        $this->assertTrue($result->success);
        Mail::assertSent(BookingPaymentReceiptMail::class, 1);
        $this->assertSame([], $fake->sent);
        $this->assertNull($payment->refresh()->receipt_email_error);
    }

    public function test_receipt_mail_and_whatsapp_render_idr_and_usd_details(): void
    {
        $payment = $this->payment([
            'status' => 'paid',
            'paid_at' => now(),
            'midtrans_transaction_id' => 'midtrans-reference',
            'midtrans_payment_type' => 'credit_card',
        ])->load('booking.tourPackage');

        $mail = new BookingPaymentReceiptMail($payment);
        $mail->assertHasSubject('Payment received: '.$payment->booking->booking_code);
        $mail->assertSeeInHtml('Payment received');
        $mail->assertSeeInHtml('Rp700.000');
        $mail->assertSeeInHtml('midtrans-reference');
        $mail->assertSeeInHtml($payment->order_id);
        $mail->assertSeeInHtml(route('checkout.payment.show', $payment->public_token));
        $mail->assertSeeInText('not an Indonesian tax invoice');

        $payment->update([
            'quote_currency' => 'USD',
            'quote_amount' => 45,
            'exchange_rate' => 18000,
            'charge_amount' => 810000,
        ]);
        $html = (new BookingPaymentReceiptMail($payment->refresh()->load('booking.tourPackage')))->render();

        $this->assertStringContainsString('$45', $html);
        $this->assertStringContainsString('Rp18.000', $html);
        $this->assertStringContainsString('Rp810.000', $html);

        $whatsapp = app(PaymentReceiptWhatsAppMessage::class)->text($payment);
        $this->assertStringContainsString('*Payment received*', $whatsapp);
        $this->assertStringContainsString('*Bromo Sunrise Private Trip*', $whatsapp);
        $this->assertStringContainsString('Amount paid: Rp810.000', $whatsapp);
        $this->assertStringContainsString('No further payment is required', $whatsapp);
        $this->assertStringContainsString('WIB', $whatsapp);
    }

    private function enableWhatspie(): FakeWhatspieClient
    {
        $fake = new FakeWhatspieClient();
        $this->app->instance(WhatspieClient::class, $fake);
        WhatsappGatewaySetting::current()->update([
            'provider' => 'whatspie',
            'is_enabled' => true,
            'api_base_url' => 'https://api.whatspie.test',
            'api_token' => 'token',
            'session_id' => 'session',
            'manual_fallback_enabled' => true,
        ]);

        return $fake;
    }

    private function payment(array $overrides = []): BookingPayment
    {
        return BookingPayment::create(array_merge([
            'booking_id' => $this->booking()->id,
            'provider' => 'midtrans',
            'order_id' => 'TJ-RECEIPT-'.strtoupper(fake()->unique()->bothify('??????')),
            'public_token' => fake()->unique()->regexify('[A-Za-z0-9]{40}'),
            'quote_currency' => 'IDR',
            'quote_amount' => 700000,
            'charge_currency' => 'IDR',
            'charge_amount' => 700000,
            'status' => 'pending',
            'snap_token' => 'receipt-token',
            'snap_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/receipt-token',
            'expires_at' => now()->addDay(),
            'sent_at' => now()->subHour(),
        ], $overrides));
    }

    private function booking(array $overrides = []): Booking
    {
        $destination = Destination::firstOrCreate(['slug' => 'receipt-bromo'], ['name' => 'Bromo']);
        $package = TourPackage::firstOrCreate(['slug' => 'receipt-bromo-sunrise'], [
            'destination_id' => $destination->id,
            'title' => ['us' => 'Bromo Sunrise Private Trip'],
            'base_price_idr' => 700000,
            'base_price_usd' => 45,
            'is_active' => true,
        ]);

        return Booking::create(array_merge([
            'booking_code' => 'TJ-RECEIPT-'.strtoupper(fake()->unique()->bothify('????')),
            'tour_package_id' => $package->id,
            'destination_id' => $destination->id,
            'name' => 'Receipt Test',
            'email' => 'receipt@example.test',
            'whatsapp' => '+6281234567890',
            'travel_date' => now()->addWeeks(3),
            'pax' => 2,
            'pickup' => 'Malang Hotel',
            'traveler_type' => 'international',
            'currency' => 'IDR',
            'subtotal' => 700000,
            'discount_total' => 0,
            'total' => 700000,
            'status' => 'confirmed',
        ], $overrides));
    }
}
