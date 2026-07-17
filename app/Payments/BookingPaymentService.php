<?php

namespace App\Payments;

use App\Gateways\Email\EmailGatewayService;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Payments\Doku\DokuClient;
use App\Payments\ExchangeRates\ExchangeRateService;
use App\Payments\Midtrans\MidtransClient;
use App\Support\BookingLanguage;
use App\Support\PublicSite;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;

class BookingPaymentService
{
    public function __construct(
        private readonly MidtransClient $midtrans,
        private readonly DokuClient $doku,
        private readonly ExchangeRateService $exchangeRates,
        private readonly PaymentSettingsService $settings,
        private readonly PaymentReceiptService $receipts,
    ) {}

    public function createPaymentRequest(Booking $booking, ?int $exchangeRate = null): BookingPayment
    {
        if ($this->settings->isManualActive()) {
            return $this->createManualPaymentRequest($booking, $exchangeRate);
        }

        if ($this->settings->dokuEnabled()) {
            return $this->createDokuPaymentRequest($booking, $exchangeRate);
        }

        return $this->createMidtransPaymentRequest($booking, $exchangeRate);
    }

    public function createMidtransPaymentRequest(Booking $booking, ?int $exchangeRate = null): BookingPayment
    {
        $this->assertBookingCanReceivePayment($booking);

        if (! $this->settings->midtransEnabled()) {
            throw new InvalidArgumentException('Midtrans payments are disabled in Payment Settings.');
        }

        [$chargeAmount, $rate, $rateSnapshot] = $this->chargeSnapshot($booking, $exchangeRate);
        $payment = BookingPayment::create([
            'booking_id' => $booking->id,
            'provider' => 'midtrans',
            'order_id' => $this->orderId($booking),
            'public_token' => Str::random(40),
            'quote_currency' => $booking->currency ?: 'IDR',
            'quote_amount' => (int) $booking->total,
            'charge_currency' => 'IDR',
            'exchange_rate' => $rate,
            'exchange_rate_snapshot' => $rateSnapshot,
            'charge_amount' => $chargeAmount,
            'status' => 'pending',
            'expires_at' => now()->addDay(),
        ]);

        try {
            $response = $this->midtrans->createSnapTransaction($this->snapPayload($payment));
        } catch (\Throwable $exception) {
            $payment->update([
                'status' => 'failed',
                'failed_at' => now(),
                'midtrans_raw_response' => ['local_error' => $exception->getMessage()],
            ]);

            throw $exception;
        }

        $payment->update([
            'snap_token' => $response['token'] ?? null,
            'snap_url' => $response['redirect_url'] ?? $response['snap_url'] ?? null,
            'midtrans_raw_response' => $response,
        ]);

        if (! filled($payment->snap_url)) {
            $payment->update([
                'status' => 'failed',
                'failed_at' => now(),
                'midtrans_raw_response' => array_merge($response, ['local_error' => 'Midtrans response did not include a Snap redirect URL.']),
            ]);

            throw new InvalidArgumentException('Midtrans response did not include a Snap redirect URL.');
        }

        return $payment->refresh();
    }

    public function createDokuPaymentRequest(Booking $booking, ?int $exchangeRate = null): BookingPayment
    {
        $this->assertBookingCanReceivePayment($booking);

        if (! $this->settings->dokuEnabled()) {
            throw new InvalidArgumentException('DOKU payments are disabled in Payment Settings.');
        }

        if (! filled($this->settings->dokuClientId()) || ! filled($this->settings->dokuSecretKey())) {
            throw new InvalidArgumentException('DOKU Client ID and Secret Key are required in Payment Settings.');
        }

        [$chargeAmount, $rate, $rateSnapshot] = $this->chargeSnapshot($booking, $exchangeRate);
        $payment = BookingPayment::create([
            'booking_id' => $booking->id,
            'provider' => 'doku',
            'gateway_environment' => $this->settings->dokuIsProduction() ? 'production' : 'sandbox',
            'order_id' => $this->dokuOrderId($booking),
            'public_token' => Str::random(40),
            'quote_currency' => $booking->currency ?: 'IDR',
            'quote_amount' => (int) $booking->total,
            'charge_currency' => 'IDR',
            'exchange_rate' => $rate,
            'exchange_rate_snapshot' => $rateSnapshot,
            'charge_amount' => $chargeAmount,
            'status' => 'pending',
            'expires_at' => now()->addDay(),
        ]);

        try {
            $response = $this->doku->createCheckoutPayment($this->dokuPayload($payment), $payment->order_id);
        } catch (\Throwable $exception) {
            $payment->update([
                'status' => 'failed',
                'failed_at' => now(),
                'doku_raw_response' => ['local_error' => $exception->getMessage()],
            ]);

            throw $exception;
        }

        $paymentResponse = $response['response']['payment'] ?? [];
        $payment->update([
            'snap_token' => $paymentResponse['token_id'] ?? null,
            'snap_url' => $paymentResponse['url'] ?? null,
            'doku_request_id' => $response['response']['headers']['request_id'] ?? $payment->order_id,
            'doku_payment_token' => $paymentResponse['token_id'] ?? null,
            'doku_raw_response' => $response,
        ]);

        if (! filled($payment->snap_url)) {
            $payment->update([
                'status' => 'failed',
                'failed_at' => now(),
                'doku_raw_response' => array_merge($response, ['local_error' => 'DOKU response did not include a payment URL.']),
            ]);

            throw new InvalidArgumentException('DOKU response did not include a payment URL.');
        }

        return $payment->refresh();
    }

    public function createManualPaymentRequest(Booking $booking, ?int $exchangeRate = null): BookingPayment
    {
        $this->assertBookingCanReceivePayment($booking);

        if (! $this->settings->isManualActive()) {
            throw new InvalidArgumentException('Manual payments are disabled in Payment Settings.');
        }

        [$chargeAmount, $rate, $rateSnapshot] = $this->chargeSnapshot($booking, $exchangeRate);
        $payment = BookingPayment::create([
            'booking_id' => $booking->id,
            'provider' => 'manual',
            'order_id' => $this->orderId($booking),
            'public_token' => Str::random(40),
            'quote_currency' => $booking->currency ?: 'IDR',
            'quote_amount' => (int) $booking->total,
            'charge_currency' => 'IDR',
            'exchange_rate' => $rate,
            'exchange_rate_snapshot' => $rateSnapshot,
            'charge_amount' => $chargeAmount,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        return $payment->refresh();
    }

    public function markManualPaymentAsPaid(BookingPayment $payment): BookingPayment
    {
        if ($payment->provider !== 'manual' || $payment->status === 'paid') {
            return $payment;
        }

        $payment->forceFill([
            'status' => 'paid',
            'paid_at' => now(),
        ])->save();

        $this->receipts->sendAutomatically($payment);

        return $payment->refresh();
    }

    public function sendInvoice(BookingPayment $payment): BookingPayment
    {
        $payment->loadMissing('booking.tourPackage.destination');

        if (! in_array($payment->status, ['pending', 'invoice_sent'], true)) {
            throw new InvalidArgumentException('A payment request email can only be sent while payment is awaiting payment.');
        }

        if (! filled($payment->booking?->email)) {
            throw new InvalidArgumentException('Booking email is required before sending invoice email.');
        }

        if (in_array($payment->provider, ['midtrans', 'doku'], true) && ! filled($payment->snap_url)) {
            throw new InvalidArgumentException('A hosted payment URL is required before sending invoice email.');
        }

        app(EmailGatewayService::class)->sendInvoice($payment);
        $payment->forceFill(['sent_at' => $payment->sent_at ?? now(), 'status' => $payment->status === 'pending' ? 'invoice_sent' : $payment->status])->save();

        return $payment->refresh();
    }

    public function markWhatsappOpened(BookingPayment $payment): BookingPayment
    {
        $payment->forceFill(['whatsapp_opened_at' => $payment->whatsapp_opened_at ?? now()])->save();

        return $payment->refresh();
    }

    public function cancel(BookingPayment $payment): BookingPayment
    {
        if (! in_array($payment->status, ['pending', 'invoice_sent'], true)) {
            return $payment;
        }

        $response = [];
        if ($payment->provider === 'midtrans') {
            try {
                $response = $this->midtrans->cancel($payment->order_id);
            } catch (\Throwable $exception) {
                $response = ['local_error' => $exception->getMessage()];
            }
        }

        $updates = [
            'status' => 'cancelled',
            'cancelled_at' => $payment->cancelled_at ?? now(),
        ];

        if ($payment->provider === 'midtrans') {
            $updates['midtrans_raw_response'] = array_merge($payment->midtrans_raw_response ?? [], ['cancel' => $response]);
        } elseif ($payment->provider === 'doku') {
            $updates['doku_raw_response'] = array_merge($payment->doku_raw_response ?? [], ['local_cancel' => ['cancelled_at' => now()->toIso8601String()]]);
        }

        $payment->update($updates);

        return $payment->refresh();
    }

    public function sync(BookingPayment $payment): BookingPayment
    {
        if ($payment->provider === 'doku') {
            return $this->applyDokuStatus($payment, $this->doku->status($payment->order_id));
        }

        if ($payment->provider === 'manual') {
            return $payment->refresh();
        }

        return $this->applyMidtransStatus($payment, $this->midtrans->status($payment->order_id));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleNotification(array $payload): ?BookingPayment
    {
        $orderId = (string) ($payload['order_id'] ?? '');
        $payment = BookingPayment::where('order_id', $orderId)->first();

        if (! $payment) {
            return null;
        }

        return $this->applyMidtransStatus($payment, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleDokuNotification(array $payload): ?BookingPayment
    {
        $orderId = (string) data_get($payload, 'order.invoice_number', data_get($payload, 'transaction.original_request_id', ''));
        $payment = BookingPayment::where('order_id', $orderId)->first();

        if (! $payment) {
            return null;
        }

        return $this->applyDokuStatus($payment, $payload);
    }

    public function verifySignature(array $payload): bool
    {
        $signature = (string) ($payload['signature_key'] ?? '');

        if ($signature === '') {
            return false;
        }

        $source = (string) ($payload['order_id'] ?? '')
            .(string) ($payload['status_code'] ?? '')
            .(string) ($payload['gross_amount'] ?? '')
            .(string) config('services.midtrans.server_key');

        return hash_equals(hash('sha512', $source), $signature);
    }

    public function verifyDokuSignature(Request $request): bool
    {
        $signature = (string) $request->header('Signature', '');

        if ($signature === '' || ! filled($this->settings->dokuClientId()) || ! filled($this->settings->dokuSecretKey())) {
            return false;
        }

        $components = [
            'Client-Id:'.(string) $request->header('Client-Id', ''),
            'Request-Id:'.(string) $request->header('Request-Id', ''),
            'Request-Timestamp:'.(string) $request->header('Request-Timestamp', ''),
            'Request-Target:'.$request->getPathInfo(),
            'Digest:'.base64_encode(hash('sha256', $request->getContent(), true)),
        ];
        $expected = 'HMACSHA256='.base64_encode(hash_hmac('sha256', implode("\n", $components), $this->settings->dokuSecretKey(), true));

        return hash_equals($expected, $signature);
    }

    public function applyMidtransStatus(BookingPayment $payment, array $payload): BookingPayment
    {
        $wasPaid = $payment->status === 'paid';
        $transactionStatus = (string) ($payload['transaction_status'] ?? $payload['status'] ?? '');
        $fraudStatus = (string) ($payload['fraud_status'] ?? '');
        $status = $this->localMidtransStatus($transactionStatus, $fraudStatus);

        if ($status === 'pending' && $payment->status === 'invoice_sent') {
            $status = 'invoice_sent';
        }
        $timestamps = $this->statusTimestamps($payment, $status);

        $payment->forceFill(array_merge([
            'status' => $status,
            'midtrans_transaction_id' => $payload['transaction_id'] ?? $payment->midtrans_transaction_id,
            'midtrans_payment_type' => $payload['payment_type'] ?? $payment->midtrans_payment_type,
            'midtrans_transaction_status' => $transactionStatus ?: $payment->midtrans_transaction_status,
            'midtrans_fraud_status' => $fraudStatus ?: $payment->midtrans_fraud_status,
            'midtrans_raw_notification' => $payload,
        ], $timestamps))->save();

        $payment->refresh();

        if ($status === 'paid' && ! $wasPaid) {
            $this->receipts->sendAutomatically($payment);
        }

        return $payment->refresh();
    }

    public function applyDokuStatus(BookingPayment $payment, array $payload): BookingPayment
    {
        $wasPaid = $payment->status === 'paid';
        $transactionStatus = strtoupper((string) data_get($payload, 'transaction.status', data_get($payload, 'status', '')));
        // DOKU Checkout emits FAILED for an individual payment-method attempt
        // while the hosted checkout remains available for another attempt.
        $status = $transactionStatus === 'FAILED'
            ? $payment->status
            : $this->localDokuStatus($transactionStatus);

        if ($status === 'pending' && $payment->status === 'invoice_sent') {
            $status = 'invoice_sent';
        }
        $timestamps = $this->statusTimestamps($payment, $status);

        $payment->forceFill(array_merge([
            'status' => $status,
            'doku_transaction_status' => $transactionStatus ?: $payment->doku_transaction_status,
            'doku_raw_notification' => $payload,
        ], $timestamps))->save();

        $payment->refresh();

        if ($status === 'paid' && ! $wasPaid) {
            $this->receipts->sendAutomatically($payment);
        }

        return $payment->refresh();
    }

    /**
     * @return array{0: int, 1: int|null, 2: array<string, mixed>|null}
     */
    public function chargeSnapshot(Booking $booking, ?int $exchangeRate = null): array
    {
        if (($booking->currency ?: 'IDR') === 'USD') {
            $quote = $this->exchangeRates->usdToIdr($exchangeRate);

            return [(int) ceil((int) $booking->total * $quote->finalRate), $quote->finalRate, $quote->snapshot()];
        }

        return [(int) $booking->total, null, null];
    }

    private function assertBookingCanReceivePayment(Booking $booking): void
    {
        $booking->loadMissing('tourPackage.destination', 'activePayment');

        if ($booking->status !== 'confirmed') {
            throw new InvalidArgumentException('Only confirmed bookings can receive payment requests.');
        }

        if (! in_array((string) ($booking->pricing_status ?: 'priced'), ['priced', 'quoted'], true) || $booking->total <= 0) {
            throw new InvalidArgumentException('A final booking price is required before creating a payment request.');
        }

        if ($booking->activePayment) {
            throw new InvalidArgumentException('This booking already has an active payment request.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function statusTimestamps(BookingPayment $payment, string $status): array
    {
        return match ($status) {
            'paid' => ['paid_at' => $payment->paid_at ?? now()],
            'expired' => ['expired_at' => $payment->expired_at ?? now()],
            'failed' => ['failed_at' => $payment->failed_at ?? now()],
            'cancelled' => ['cancelled_at' => $payment->cancelled_at ?? now()],
            default => [],
        };
    }

    private function localMidtransStatus(string $transactionStatus, string $fraudStatus): string
    {
        return match ($transactionStatus) {
            'settlement' => 'paid',
            'capture' => $fraudStatus === 'challenge' ? 'pending' : 'paid',
            'pending' => 'pending',
            'expire' => 'expired',
            'cancel' => 'cancelled',
            'deny', 'failure' => 'failed',
            default => 'pending',
        };
    }

    private function localDokuStatus(string $transactionStatus): string
    {
        return match ($transactionStatus) {
            'SUCCESS' => 'paid',
            'EXPIRED' => 'expired',
            'FAILED', 'PENDING', 'TIMEOUT', 'REDIRECT' => 'pending',
            default => 'pending',
        };
    }

    private function orderId(Booking $booking): string
    {
        do {
            $orderId = "{$booking->booking_code}-PAY-".strtoupper(Str::random(5));
        } while (BookingPayment::where('order_id', $orderId)->exists());

        return $orderId;
    }

    private function dokuOrderId(Booking $booking): string
    {
        do {
            $orderId = 'TJ'.$booking->id.'P'.strtoupper(Str::random(8));
        } while (BookingPayment::where('order_id', $orderId)->exists());

        return $orderId;
    }

    /**
     * @return array<string, mixed>
     */
    private function snapPayload(BookingPayment $payment): array
    {
        $booking = $payment->booking;
        $paymentUrl = route('checkout.payment.show', $payment->public_token);

        return [
            'transaction_details' => [
                'order_id' => $payment->order_id,
                'gross_amount' => $payment->charge_amount,
            ],
            'customer_details' => [
                'first_name' => $booking->name,
                'email' => $booking->email,
                'phone' => $booking->whatsapp,
            ],
            'item_details' => [[
                'id' => $booking->booking_code,
                'price' => $payment->charge_amount,
                'quantity' => 1,
                'name' => str(PublicSite::localized($booking->tourPackage?->title, 'us', $booking->booking_code))->limit(50)->toString(),
            ]],
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit' => 'day',
                'duration' => 1,
            ],
            'callbacks' => [
                'finish' => $paymentUrl,
                'unfinish' => $paymentUrl,
                'error' => $paymentUrl,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dokuPayload(BookingPayment $payment): array
    {
        $booking = $payment->booking;
        $paymentUrl = route('checkout.payment.show', $payment->public_token);
        $language = BookingLanguage::normalize($booking?->communication_language);

        return [
            'order' => [
                'amount' => $payment->charge_amount,
                'invoice_number' => $payment->order_id,
                'currency' => 'IDR',
                'callback_url' => $paymentUrl,
                'callback_url_result' => $paymentUrl,
                'auto_redirect' => false,
                'disable_retry_payment' => false,
                'language' => $language === 'id' ? 'ID' : 'EN',
                'line_items' => [[
                    'id' => $booking->booking_code,
                    'name' => str(PublicSite::localized($booking->tourPackage?->title, 'us', $booking->booking_code))->limit(80)->toString(),
                    'price' => $payment->charge_amount,
                    'quantity' => 1,
                ]],
            ],
            'payment' => [
                'payment_due_date' => 1440,
            ],
            'customer' => [
                'id' => $booking->booking_code,
                'name' => $booking->name,
                'email' => $booking->email,
                'phone' => preg_replace('/\D+/', '', (string) $booking->whatsapp),
                'country' => 'ID',
            ],
        ];
    }
}
