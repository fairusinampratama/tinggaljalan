<?php

namespace App\Payments;

use App\Gateways\Email\EmailGatewayService;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Payments\ExchangeRates\ExchangeRateService;
use App\Payments\Midtrans\MidtransClient;
use App\Support\PublicSite;
use Illuminate\Support\Str;
use InvalidArgumentException;

class BookingPaymentService
{
    public function __construct(
        private readonly MidtransClient $midtrans,
        private readonly ExchangeRateService $exchangeRates,
        private readonly PaymentSettingsService $settings,
        private readonly PaymentReceiptService $receipts,
    ) {
    }

    public function createPaymentRequest(Booking $booking, ?int $exchangeRate = null): BookingPayment
    {
        $booking->loadMissing('tourPackage.destination', 'activePayment');

        if (! $this->settings->midtransEnabled()) {
            throw new InvalidArgumentException('Midtrans payments are disabled in Payment Settings.');
        }

        if ($booking->status !== 'confirmed') {
            throw new InvalidArgumentException('Only confirmed bookings can receive payment requests.');
        }

        if ($booking->activePayment) {
            throw new InvalidArgumentException('This booking already has an active payment request.');
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

    public function sendInvoice(BookingPayment $payment): BookingPayment
    {
        $payment->loadMissing('booking.tourPackage.destination');

        if (! in_array($payment->status, ['pending', 'invoice_sent'], true)) {
            throw new InvalidArgumentException('A payment request email can only be sent while payment is awaiting payment.');
        }

        if (! filled($payment->booking?->email)) {
            throw new InvalidArgumentException('Booking email is required before sending invoice email.');
        }

        if (! filled($payment->snap_url)) {
            throw new InvalidArgumentException('A Midtrans Snap URL is required before sending invoice email.');
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

        try {
            $response = $this->midtrans->cancel($payment->order_id);
        } catch (\Throwable $exception) {
            $response = ['local_error' => $exception->getMessage()];
        }

        $payment->update([
            'status' => 'cancelled',
            'cancelled_at' => $payment->cancelled_at ?? now(),
            'midtrans_raw_response' => array_merge($payment->midtrans_raw_response ?? [], ['cancel' => $response]),
        ]);

        return $payment->refresh();
    }

    public function sync(BookingPayment $payment): BookingPayment
    {
        return $this->applyMidtransStatus($payment, $this->midtrans->status($payment->order_id));
    }

    /**
     * @param array<string, mixed> $payload
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

    public function applyMidtransStatus(BookingPayment $payment, array $payload): BookingPayment
    {
        $wasPaid = $payment->status === 'paid';
        $transactionStatus = (string) ($payload['transaction_status'] ?? $payload['status'] ?? '');
        $fraudStatus = (string) ($payload['fraud_status'] ?? '');
        $status = $this->localStatus($transactionStatus, $fraudStatus);

        if ($status === 'pending' && $payment->status === 'invoice_sent') {
            $status = 'invoice_sent';
        }
        $timestamps = match ($status) {
            'paid' => ['paid_at' => $payment->paid_at ?? now()],
            'expired' => ['expired_at' => $payment->expired_at ?? now()],
            'failed' => ['failed_at' => $payment->failed_at ?? now()],
            'cancelled' => ['cancelled_at' => $payment->cancelled_at ?? now()],
            default => [],
        };

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

    private function localStatus(string $transactionStatus, string $fraudStatus): string
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

    private function orderId(Booking $booking): string
    {
        do {
            $orderId = "{$booking->booking_code}-PAY-".strtoupper(Str::random(5));
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
}