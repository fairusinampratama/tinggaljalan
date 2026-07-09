<?php

namespace App\Http\Controllers;

use App\Models\BookingPayment;
use App\Models\TourPackage;
use App\Payments\BookingPaymentService;
use App\Payments\PaymentSettingsService;
use App\Support\BookingLanguage;
use App\Support\InertiaPublicData;
use App\Support\PublicSite;
use App\Support\Seo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class CheckoutPaymentController extends Controller
{
    public function show(Request $request, string $payment)
    {
        $payment = BookingPayment::query()
            ->with('booking.tourPackage.destination')
            ->where('public_token', $payment)
            ->firstOrFail();
        $booking = $payment->booking;
        $language = BookingLanguage::normalize($request->session()->get('language', $booking->communication_language));
        $paymentSettings = app(PaymentSettingsService::class);
        $presentation = $this->presentation($payment, $paymentSettings, $language);

        return Inertia::render('CheckoutPaymentStatusPage', [
            'language' => $language,
            'payment' => [
                'publicToken' => $payment->public_token,
                'status' => $payment->status,
                'statusLabel' => $presentation['statusLabel'],
                'headline' => $presentation['headline'],
                'body' => $presentation['body'],
                'tone' => $presentation['tone'],
                'canPay' => $presentation['canPay'],
                'hasSnapUrl' => filled($payment->snap_url),
                'timeline' => $presentation['timeline'],
                'quoteCurrency' => $payment->quote_currency,
                'quoteAmount' => $payment->quote_amount,
                'chargeCurrency' => $payment->charge_currency,
                'chargeAmount' => $payment->charge_amount,
                'exchangeRate' => $payment->exchange_rate,
                'provider' => $payment->provider,
                'providerLabel' => $paymentSettings->publicLabel($payment->provider),
                'bookingNote' => $paymentSettings->bookingNote($payment->provider),
                'usdNote' => $paymentSettings->usdNote($payment->provider),
                'manualBankAccounts' => $paymentSettings->manualBankAccounts($payment->provider),
                'snapUrl' => $payment->snap_url,
                'expiresAt' => $payment->expires_at ? BookingLanguage::date($payment->expires_at->timezone('Asia/Jakarta'), $language, true).' WIB' : null,
                'paidAt' => $payment->paid_at ? BookingLanguage::date($payment->paid_at->timezone('Asia/Jakarta'), $language, true).' WIB' : null,
                'copy' => $this->pageCopy($language, $paymentSettings, $payment->provider),
                'booking' => [
                    'code' => $booking->booking_code,
                    'name' => $booking->name,
                    'packageTitle' => $booking->tourPackage
                        ? PublicSite::localized($booking->tourPackage->title, $language, $booking->tourPackage->slug)
                        : '-',
                    'travelDate' => BookingLanguage::date($booking->travel_date, $language),
                    'pax' => $booking->pax,
                ],
            ],
            'routes' => InertiaPublicData::routes(TourPackage::query()->with(['destination', 'packageAddOns', 'itineraryItems', 'newsArticles'])->active()->ordered()->get()),
            'route' => $booking->tourPackage ? InertiaPublicData::route($booking->tourPackage) : null,
            'whatsappUrl' => PublicSite::whatsappUrl([
                BookingLanguage::translate('booking.support_question', [], $language),
                BookingLanguage::translate('booking.booking_code', [], $language).": {$booking->booking_code}",
                BookingLanguage::translate('booking.charge_label', ['provider' => $paymentSettings->publicLabel($payment->provider)], $language).': '.PublicSite::formatMoney($payment->charge_amount, 'IDR'),
            ]),
            'seo' => Seo::noindex([
                'title' => "Payment {$booking->booking_code} | Tinggal Jalan",
                'description' => 'Secure payment page for a Tinggal Jalan booking.',
                'canonical' => route('checkout.payment.show', $payment->public_token),
            ]),
        ]);
    }

    public function status(string $payment, BookingPaymentService $payments): JsonResponse
    {
        $payment = BookingPayment::query()
            ->where('public_token', $payment)
            ->firstOrFail();

        if ($this->isTerminal($payment->status)) {
            return response()->json($this->statusPayload($payment));
        }

        $lock = Cache::lock("booking-payment-status:{$payment->id}", 8);

        if (! $lock->get()) {
            return response()->json($this->statusPayload($payment));
        }

        try {
            $payment = $payments->sync($payment);

            return response()->json($this->statusPayload($payment));
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json($this->statusPayload($payment->refresh(), false));
        } finally {
            $lock->release();
        }
    }

    /**
     * @return array{status: string, terminal: bool, checkSucceeded: bool, checkedAt: string}
     */
    private function statusPayload(BookingPayment $payment, bool $checkSucceeded = true): array
    {
        return [
            'status' => $payment->status,
            'terminal' => $this->isTerminal($payment->status),
            'checkSucceeded' => $checkSucceeded,
            'checkedAt' => now()->toIso8601String(),
        ];
    }

    private function pageCopy(string $language, PaymentSettingsService $settings, string $provider): array
    {
        $keys = ['eyebrow', 'charge', 'original_quote', 'expires', 'paid_at', 'exchange_rate', 'missing_link', 'ask_whatsapp', 'back_home', 'booking_summary', 'package', 'travel_date', 'customer', 'guests', 'checking', 'checked', 'refresh_failed', 'last_checked', 'usd_note', 'bank_accounts', 'account_name', 'account_number'];

        return collect($keys)->mapWithKeys(fn (string $key): array => [
            $key => BookingLanguage::translate('booking.payment_page.'.$key, ['provider' => $settings->publicLabel($provider)], $language),
        ])->all() + [
            'pay_securely' => BookingLanguage::translate('booking.pay_securely', ['provider' => $settings->publicLabel($provider)], $language),
        ];
    }
    private function isTerminal(string $status): bool
    {
        return in_array($status, ['paid', 'expired', 'failed', 'cancelled'], true);
    }
    /**
     * @return array<string, mixed>
     */
    private function presentation(BookingPayment $payment, PaymentSettingsService $settings, string $language): array
    {
        $status = $payment->status;
        $hasSnapUrl = filled($payment->snap_url);
        $isManual = $payment->provider === 'manual';
        $canPay = in_array($status, ['pending', 'invoice_sent'], true) && ($hasSnapUrl || $isManual);

        $t = fn (string $key, array $replace = []): string => BookingLanguage::translate('booking.payment_page.'.$key, $replace, $language);

        $copy = match ($status) {
            'paid' => [
                $t('paid_headline'),
                $t('paid_body'),
                'success',
            ],
            'expired' => [
                $t('expired_headline'),
                $t('expired_body'),
                'warning',
            ],
            'failed', 'cancelled' => [
                $t('failed_headline'),
                $t('failed_body'),
                'danger',
            ],
            default => ($hasSnapUrl || $isManual)
                ? [
                    $t('pending_headline'),
                    $isManual ? $t('manual_pending_body') : $t('pending_body', ['provider' => $settings->publicLabel($payment->provider)]),
                    'info',
                ]
                : [
                    $t('preparing_headline'),
                    $t('preparing_body'),
                    'warning',
                ],
        };

        return [
            'statusLabel' => $t('status_'.$status),
            'headline' => $copy[0],
            'body' => $copy[1],
            'tone' => $copy[2],
            'canPay' => $canPay,
            'timeline' => $this->timeline($status, $canPay, $language),
        ];
    }

    /**
     * @return array<int, array{label: string, state: string}>
     */
    private function timeline(string $status, bool $canPay, string $language): array
    {
        $paymentState = match ($status) {
            'paid' => 'complete',
            'expired', 'failed', 'cancelled' => 'problem',
            default => $canPay ? 'current' : 'problem',
        };

        return [
            ['label' => BookingLanguage::translate('booking.payment_page.request_sent', [], $language), 'state' => 'complete'],
            ['label' => BookingLanguage::translate('booking.payment_page.availability_confirmed', [], $language), 'state' => 'complete'],
            ['label' => BookingLanguage::translate('booking.payment_page.payment', [], $language), 'state' => $paymentState],
            ['label' => BookingLanguage::translate('booking.payment_page.trip_confirmed', [], $language), 'state' => $status === 'paid' ? 'complete' : 'upcoming'],
        ];
    }
}