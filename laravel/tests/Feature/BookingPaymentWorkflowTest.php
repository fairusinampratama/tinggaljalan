<?php

namespace Tests\Feature;

use App\Filament\Support\BookingPaymentHandoff;
use App\Mail\BookingPaymentInvoiceMail;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Destination;
use App\Models\TourPackage;
use App\Models\User;
use App\Payments\BookingPaymentService;
use App\Payments\ExchangeRates\ExchangeRateClient;
use App\Payments\Midtrans\MidtransClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use InvalidArgumentException;
use RuntimeException;
use Tests\Support\FakeExchangeRateClient;
use Tests\Support\FakeMidtransClient;
use Tests\TestCase;

class BookingPaymentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private FakeMidtransClient $midtrans;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.midtrans.server_key' => 'server-key',
            'services.exchange_rates.usd_idr_buffer_percent' => 2,
        ]);
        $this->midtrans = new FakeMidtransClient();
        $this->app->instance(MidtransClient::class, $this->midtrans);
        $this->app->instance(ExchangeRateClient::class, new FakeExchangeRateClient());
    }

    public function test_invoice_email_can_be_sent_and_records_sent_timestamp(): void
    {
        Mail::fake();
        $payment = app(BookingPaymentService::class)->createPaymentRequest($this->booking());

        $sent = app(BookingPaymentService::class)->sendInvoice($payment);

        Mail::assertSent(BookingPaymentInvoiceMail::class);
        $this->assertSame('invoice_sent', $sent->status);
        $this->assertNotNull($sent->sent_at);
        $this->assertNull($sent->whatsapp_opened_at);
    }

    public function test_payment_whatsapp_handoff_records_opened_timestamp_without_mutating_payment_snapshot(): void
    {
        $payment = app(BookingPaymentService::class)->createPaymentRequest($this->booking());
        $original = $payment->only(['quote_amount', 'charge_amount', 'status', 'sent_at', 'paid_at', 'cancelled_at']);
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.booking-payments.whatsapp', $payment));

        $response->assertStatus(302);
        $this->assertStringStartsWith('https://wa.me/6281234567890', $response->headers->get('Location'));

        $payment->refresh();
        $this->assertNotNull($payment->whatsapp_opened_at);
        $this->assertSame($original['quote_amount'], $payment->quote_amount);
        $this->assertSame($original['charge_amount'], $payment->charge_amount);
        $this->assertSame($original['status'], $payment->status);
        $this->assertSame($original['sent_at'], $payment->sent_at);
        $this->assertSame($original['paid_at'], $payment->paid_at);
        $this->assertSame($original['cancelled_at'], $payment->cancelled_at);
    }

    public function test_booking_payment_handoff_statuses_describe_email_and_whatsapp_delivery(): void
    {
        $this->assertSame('Not requested', BookingPaymentHandoff::status(null));

        $this->assertSame('Payment created', BookingPaymentHandoff::status($this->paymentForStatus('pending')));
        $this->assertSame('Email sent', BookingPaymentHandoff::status($this->paymentForStatus('invoice_sent', ['sent_at' => now()])));
        $this->assertSame('WA opened', BookingPaymentHandoff::status($this->paymentForStatus('pending', ['whatsapp_opened_at' => now()])));
        $this->assertSame('Email + WA opened', BookingPaymentHandoff::status($this->paymentForStatus('invoice_sent', ['sent_at' => now(), 'whatsapp_opened_at' => now()])));
        $this->assertSame('Paid', BookingPaymentHandoff::status($this->paymentForStatus('paid')));
        $this->assertSame('Receipt sent', BookingPaymentHandoff::status($this->paymentForStatus('paid', ['receipt_email_sent_at' => now(), 'receipt_whatsapp_sent_at' => now()])));
        $this->assertSame('Email receipt sent', BookingPaymentHandoff::status($this->paymentForStatus('paid', ['receipt_email_sent_at' => now()])));
        $this->assertSame('WA confirmation sent', BookingPaymentHandoff::status($this->paymentForStatus('paid', ['receipt_whatsapp_sent_at' => now()])));
        $this->assertSame('Receipt partially failed', BookingPaymentHandoff::status($this->paymentForStatus('paid', ['receipt_email_sent_at' => now(), 'receipt_whatsapp_failed_at' => now()])));
        $this->assertSame('Receipt not delivered', BookingPaymentHandoff::status($this->paymentForStatus('paid', ['receipt_notifications_attempted_at' => now(), 'receipt_email_failed_at' => now(), 'receipt_whatsapp_failed_at' => now()])));
        $this->assertSame('Closed', BookingPaymentHandoff::status($this->paymentForStatus('expired')));
    }

    public function test_public_payment_page_loads_by_public_token_only(): void
    {
        $payment = app(BookingPaymentService::class)->createPaymentRequest($this->booking());

        $this->get("/checkout/payment/{$payment->public_token}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('CheckoutPaymentStatusPage')
                ->where('payment.publicToken', $payment->public_token)
                ->where('payment.booking.code', $payment->booking->booking_code)
                ->where('payment.chargeAmount', $payment->charge_amount));

        $this->get('/checkout/payment/not-real-token')->assertNotFound();
    }

    public function test_public_payment_page_presents_customer_states_clearly(): void
    {
        foreach ([
            'pending' => ['Booking confirmed, payment required', true],
            'invoice_sent' => ['Booking confirmed, payment required', true],
            'paid' => ['Payment received, your booking is secured', false],
            'expired' => ['Payment link expired', false],
            'failed' => ['Payment was not completed', false],
            'cancelled' => ['Payment was not completed', false],
        ] as $status => [$headline, $canPay]) {
            $payment = $this->paymentForStatus($status);

            $this->get("/checkout/payment/{$payment->public_token}")
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('CheckoutPaymentStatusPage')
                    ->where('payment.status', $status)
                    ->where('payment.headline', $headline)
                    ->where('payment.canPay', $canPay)
                    ->where('payment.timeline.0.label', 'Request sent')
                    ->where('payment.timeline.1.label', 'Availability confirmed')
                    ->where('payment.timeline.2.label', 'Payment')
                    ->where('payment.timeline.3.label', 'Booking secured'));
        }
    }

    public function test_pay_button_requires_payable_status_and_snap_url(): void
    {
        $payment = $this->paymentForStatus('pending', ['snap_url' => null]);

        $this->get("/checkout/payment/{$payment->public_token}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('payment.status', 'pending')
                ->where('payment.hasSnapUrl', false)
                ->where('payment.canPay', false)
                ->where('payment.headline', 'Payment link is being prepared'));
    }

    public function test_midtrans_response_without_snap_url_marks_payment_failed(): void
    {
        $this->midtrans->snapResponse = ['token' => 'fake-token-without-url'];
        $booking = $this->booking();

        $this->expectException(InvalidArgumentException::class);

        try {
            app(BookingPaymentService::class)->createPaymentRequest($booking);
        } finally {
            $payment = $booking->payments()->latest()->first();

            $this->assertSame('failed', $payment?->status);
            $this->assertNotNull($payment?->failed_at);
            $this->assertSame('Midtrans response did not include a Snap redirect URL.', $payment?->midtrans_raw_response['local_error'] ?? null);
        }
    }

    public function test_invoice_email_requires_snap_url(): void
    {
        Mail::fake();
        $payment = $this->paymentForStatus('pending', ['snap_url' => null]);

        $this->expectException(InvalidArgumentException::class);

        try {
            app(BookingPaymentService::class)->sendInvoice($payment);
        } finally {
            Mail::assertNothingSent();
        }
    }

    public function test_invoice_email_requires_customer_email(): void
    {
        Mail::fake();
        $payment = app(BookingPaymentService::class)->createPaymentRequest($this->booking(['email' => null]));

        $this->expectException(InvalidArgumentException::class);

        try {
            app(BookingPaymentService::class)->sendInvoice($payment);
        } finally {
            Mail::assertNothingSent();
        }
    }

    public function test_production_like_mailer_rejects_placeholder_from_address(): void
    {
        Mail::fake();
        config([
            'mail.default' => 'smtp',
            'mail.from.address' => 'hello@example.com',
        ]);
        $payment = app(BookingPaymentService::class)->createPaymentRequest($this->booking());

        $this->expectException(InvalidArgumentException::class);

        try {
            app(BookingPaymentService::class)->sendInvoice($payment);
        } finally {
            $payment->refresh();
            $this->assertNull($payment->sent_at);
            $this->assertSame('pending', $payment->status);
            Mail::assertNothingSent();
        }
    }

    public function test_mail_send_failure_keeps_payment_snapshot_unchanged(): void
    {
        $payment = app(BookingPaymentService::class)->createPaymentRequest($this->booking());
        $original = $payment->only(['quote_amount', 'charge_amount', 'status', 'sent_at', 'paid_at', 'cancelled_at']);
        Mail::shouldReceive('to')->once()->andThrow(new \Exception('SMTP provider is down'));

        $this->expectException(\Exception::class);

        try {
            app(BookingPaymentService::class)->sendInvoice($payment);
        } finally {
            $payment->refresh();
            $this->assertSame($original['quote_amount'], $payment->quote_amount);
            $this->assertSame($original['charge_amount'], $payment->charge_amount);
            $this->assertSame($original['status'], $payment->status);
            $this->assertNull($payment->sent_at);
            $this->assertNull($payment->paid_at);
            $this->assertNull($payment->cancelled_at);
        }
    }

    public function test_invoice_mailable_contains_booking_payment_and_support_details(): void
    {
        $payment = app(BookingPaymentService::class)->createPaymentRequest($this->booking([
            'booking_code' => 'TJ-MAIL-TEST',
            'name' => 'Mail Template Guest',
            'pickup' => 'Malang Hotel',
            'currency' => 'USD',
            'selected_add_ons' => [
                [
                    'slug' => 'private-guide',
                    'title' => ['us' => 'Private guide'],
                    'pricing_type' => 'per_booking',
                ],
            ],
            'voucher_code' => 'BROMO10',
            'discount_total' => 10,
            'total' => 120,
        ]), 16500);
        $mailable = new BookingPaymentInvoiceMail($payment->load('booking.tourPackage'));

        $mailable->assertHasSubject('Tinggal Jalan payment request: TJ-MAIL-TEST');
        $mailable->assertSeeInHtml('Your Tinggal Jalan booking is confirmed. Complete payment securely with Secure Midtrans payment link.');
        $mailable->assertSeeInHtml('Mail Template Guest');
        $mailable->assertSeeInHtml('TJ-MAIL-TEST');
        $mailable->assertSeeInHtml('Bromo Sunrise');
        $mailable->assertSeeInHtml('Malang Hotel');
        $mailable->assertSeeInHtml('Guests');
        $mailable->assertSeeInHtml('2');
        $mailable->assertSeeInHtml('Secure Midtrans payment link IDR charge');
        $mailable->assertSeeInHtml('Rp1.980.000');
        $mailable->assertSeeInHtml('1 USD = Rp16.500');
        $mailable->assertSeeInHtml('Your original quote stays in USD');
        $mailable->assertSeeInHtml('BROMO10');
        $mailable->assertSeeInHtml('Private guide');
        $mailable->assertSeeInHtml('What happens next');
        $mailable->assertSeeInHtml('Pay securely with Secure Midtrans payment link');
        $mailable->assertSeeInHtml(route('checkout.payment.show', $payment->public_token));
        $mailable->assertSeeInHtml('Contact Tinggal Jalan on WhatsApp');
        $mailable->assertSeeInHtml($payment->order_id);
        $mailable->assertSeeInText('Mail Template Guest');
        $mailable->assertSeeInText('TJ-MAIL-TEST');
        $mailable->assertSeeInText('Bromo Sunrise');
        $mailable->assertSeeInText('Secure Midtrans payment link IDR charge');
        $mailable->assertSeeInText('Rp1.980.000');
        $mailable->assertSeeInText('1 USD = Rp16.500');
        $mailable->assertSeeInText('Private guide');
        $mailable->assertSeeInText('Pay securely with Secure Midtrans payment link');
        $mailable->assertSeeInText(route('checkout.payment.show', $payment->public_token));
        $mailable->assertSeeInText('WhatsApp');
    }

    public function test_invoice_mailable_handles_idr_and_missing_optional_details(): void
    {
        $payment = app(BookingPaymentService::class)->createPaymentRequest($this->booking([
            'booking_code' => 'TJ-IDR-MAIL',
            'pickup' => null,
            'selected_add_ons' => null,
            'voucher_code' => null,
            'discount_total' => 0,
            'currency' => 'IDR',
            'total' => 500000,
        ]));
        $mailable = new BookingPaymentInvoiceMail($payment->load('booking.tourPackage'));

        $mailable->assertSeeInHtml('TJ-IDR-MAIL');
        $mailable->assertSeeInHtml('Rp500.000');
        $mailable->assertSeeInHtml('Secure Midtrans payment link processes this payment in IDR');
        $mailable->assertDontSeeInHtml('Exchange rate');
        $mailable->assertDontSeeInHtml('Your original quote stays in USD');
        $mailable->assertSeeInText('Pickup: -');
        $mailable->assertSeeInText('Traveler type: international');
        $mailable->assertDontSeeInText('Exchange rate');
    }


    public function test_public_status_check_reconciles_pending_payment_with_midtrans(): void
    {
        $payment = $this->paymentForStatus('pending');
        $this->midtrans->statusPayload = [
            'transaction_status' => 'settlement',
            'transaction_id' => 'midtrans-paid-transaction',
            'payment_type' => 'credit_card',
        ];

        $response = $this->getJson(route('checkout.payment.status', $payment->public_token));

        $response->assertOk()
            ->assertJson([
                'status' => 'paid',
                'terminal' => true,
                'checkSucceeded' => true,
            ])
            ->assertJsonCount(4);
        $this->assertSame([$payment->order_id], $this->midtrans->statusOrderIds);
        $this->assertSame('paid', $payment->refresh()->status);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_public_status_check_skips_midtrans_for_terminal_payment(): void
    {
        $payment = $this->paymentForStatus('paid');

        $this->getJson(route('checkout.payment.status', $payment->public_token))
            ->assertOk()
            ->assertJson([
                'status' => 'paid',
                'terminal' => true,
                'checkSucceeded' => true,
            ]);

        $this->assertSame([], $this->midtrans->statusOrderIds);
    }

    public function test_public_status_check_rejects_invalid_token(): void
    {
        $this->getJson('/checkout/payment/not-a-real-token/status')->assertNotFound();
    }

    public function test_public_status_check_retries_after_midtrans_failure_without_mutating_payment(): void
    {
        $payment = $this->paymentForStatus('invoice_sent', ['sent_at' => now()]);
        $this->midtrans->statusException = new RuntimeException('Midtrans status API unavailable');

        $this->getJson(route('checkout.payment.status', $payment->public_token))
            ->assertOk()
            ->assertJson([
                'status' => 'invoice_sent',
                'terminal' => false,
                'checkSucceeded' => false,
            ]);

        $this->assertSame('invoice_sent', $payment->refresh()->status);
        $this->assertNotNull($payment->sent_at);
    }

    public function test_public_status_check_deduplicates_concurrent_provider_calls(): void
    {
        $payment = $this->paymentForStatus('pending');
        $lock = Cache::lock("booking-payment-status:{$payment->id}", 8);
        $this->assertTrue($lock->get());

        try {
            $this->getJson(route('checkout.payment.status', $payment->public_token))
                ->assertOk()
                ->assertJson([
                    'status' => 'pending',
                    'terminal' => false,
                    'checkSucceeded' => true,
                ]);
        } finally {
            $lock->release();
        }

        $this->assertSame([], $this->midtrans->statusOrderIds);
    }

    public function test_pending_midtrans_status_preserves_invoice_sent_handoff_state(): void
    {
        $payment = $this->paymentForStatus('invoice_sent', ['sent_at' => now()]);
        $this->midtrans->statusPayload = ['transaction_status' => 'pending'];

        $this->getJson(route('checkout.payment.status', $payment->public_token))
            ->assertOk()
            ->assertJsonPath('status', 'invoice_sent');

        $this->assertSame('invoice_sent', $payment->refresh()->status);
        $this->assertNotNull($payment->sent_at);
    }
    public function test_midtrans_webhook_settlement_marks_payment_paid_without_completing_booking(): void
    {
        $booking = $this->booking();
        $payment = app(BookingPaymentService::class)->createPaymentRequest($booking);
        $grossAmount = number_format($payment->charge_amount, 2, '.', '');

        $this->postJson('/midtrans/webhook', [
            'order_id' => $payment->order_id,
            'status_code' => '200',
            'gross_amount' => $grossAmount,
            'transaction_status' => 'settlement',
            'signature_key' => hash('sha512', $payment->order_id.'200'.$grossAmount.'server-key'),
        ])->assertOk();

        $this->assertSame('paid', $payment->refresh()->status);
        $this->assertSame('confirmed', $booking->refresh()->status);
        $this->assertNull($booking->completed_at);
    }

    public function test_expired_payment_does_not_cancel_booking(): void
    {
        $booking = $this->booking();
        $payment = app(BookingPaymentService::class)->createPaymentRequest($booking);
        $grossAmount = number_format($payment->charge_amount, 2, '.', '');

        $this->postJson('/midtrans/webhook', [
            'order_id' => $payment->order_id,
            'status_code' => '200',
            'gross_amount' => $grossAmount,
            'transaction_status' => 'expire',
            'signature_key' => hash('sha512', $payment->order_id.'200'.$grossAmount.'server-key'),
        ])->assertOk();

        $this->assertSame('expired', $payment->refresh()->status);
        $this->assertNotNull($payment->expired_at);
        $this->assertSame('confirmed', $booking->refresh()->status);
        $this->assertNull($booking->cancelled_at);
    }

    public function test_cancel_and_sync_actions_use_midtrans_client_without_changing_booking_total(): void
    {
        $booking = $this->booking(['total' => 900000, 'subtotal' => 900000]);
        $payment = app(BookingPaymentService::class)->createPaymentRequest($booking);

        app(BookingPaymentService::class)->cancel($payment);

        $this->assertSame(['cancelled' => [$payment->order_id]], ['cancelled' => $this->midtrans->cancelledOrderIds]);
        $this->assertSame(900000, $booking->refresh()->total);
        $this->assertSame('cancelled', $payment->refresh()->status);
    }

    private function paymentForStatus(string $status, array $overrides = []): BookingPayment
    {
        return BookingPayment::create(array_merge([
            'booking_id' => $this->booking()->id,
            'provider' => 'midtrans',
            'order_id' => 'TJ-STATE-'.strtoupper(fake()->bothify('??????')),
            'public_token' => fake()->regexify('[A-Za-z0-9]{40}'),
            'quote_currency' => 'IDR',
            'quote_amount' => 500000,
            'charge_currency' => 'IDR',
            'charge_amount' => 500000,
            'status' => $status,
            'snap_token' => 'fake-snap-token',
            'snap_url' => in_array($status, ['pending', 'invoice_sent'], true) ? 'https://app.sandbox.midtrans.com/snap/v4/redirection/fake-snap-token' : null,
            'expires_at' => now()->addDay(),
            'paid_at' => $status === 'paid' ? now() : null,
            'expired_at' => $status === 'expired' ? now() : null,
            'failed_at' => $status === 'failed' ? now() : null,
            'cancelled_at' => $status === 'cancelled' ? now() : null,
        ], $overrides));
    }

    public function test_paid_payment_cannot_send_payment_request_email(): void
    {
        Mail::fake();
        $payment = app(BookingPaymentService::class)->createPaymentRequest($this->booking());
        $payment->update(['status' => 'paid', 'paid_at' => now()]);

        try {
            app(BookingPaymentService::class)->sendInvoice($payment->refresh());
            $this->fail('Paid payment unexpectedly sent a payment request email.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('only be sent while payment is awaiting payment', $exception->getMessage());
        }

        Mail::assertNothingSent();
        $this->assertNull($payment->refresh()->sent_at);
    }
    private function booking(array $overrides = []): Booking
    {
        $destination = Destination::firstOrCreate([
            'slug' => 'bromo',
        ], [
            'name' => 'Bromo',
        ]);
        $package = TourPackage::firstOrCreate([
            'slug' => 'bromo-sunrise',
        ], [
            'destination_id' => $destination->id,
            'title' => ['us' => 'Bromo Sunrise'],
            'base_price_idr' => 500000,
            'base_price_usd' => 35,
            'is_active' => true,
        ]);

        return Booking::create(array_merge([
            'booking_code' => 'TJ-FEATURE-'.strtoupper(fake()->bothify('????')),
            'tour_package_id' => $package->id,
            'destination_id' => $destination->id,
            'name' => 'Payment Feature Test',
            'email' => 'payment-feature@example.test',
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