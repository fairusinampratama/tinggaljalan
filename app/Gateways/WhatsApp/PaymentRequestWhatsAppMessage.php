<?php

namespace App\Gateways\WhatsApp;

use App\Models\BookingPayment;
use App\Support\BookingLanguage;
use App\Support\PublicSite;

class PaymentRequestWhatsAppMessage
{
    public function text(BookingPayment $payment): string
    {
        $payment->loadMissing('booking.tourPackage');
        $booking = $payment->booking;
        $language = BookingLanguage::normalize($booking?->communication_language);
        $package = PublicSite::localized($booking?->tourPackage?->title, $language, $booking?->booking_code ?? 'Tinggal Jalan');
        $customer = trim((string) $booking?->name) ?: BookingLanguage::translate('booking.traveler', [], $language);
        $expiresAt = BookingLanguage::date($payment->expires_at?->timezone('Asia/Jakarta'), $language, true).' WIB';
        $paymentUrl = route('checkout.payment.show', $payment->public_token);
        $t = fn (string $key, array $replace = []): string => BookingLanguage::translate("booking.{$key}", $replace, $language);

        $lines = [$t('greeting', ['name' => $customer]), '', $t('request_confirmed'), '', "*{$package}*", $t('booking_code').": {$booking?->booking_code}", $t('travel_date').': '.BookingLanguage::date($booking?->travel_date, $language), $t('guests').": {$booking?->pax}", '', '*'.$t('payment_summary').'*'];

        if (($payment->quote_currency ?: 'IDR') === 'USD') {
            $lines[] = $t('original_quote').': '.PublicSite::formatMoney($payment->quote_amount, 'USD');
            $lines[] = $t('midtrans_charge').': '.PublicSite::formatMoney($payment->charge_amount, 'IDR');
            $lines[] = $t('rate_used').': USD 1 = '.PublicSite::formatMoney($payment->exchange_rate, 'IDR');
        } else {
            $lines[] = $t('amount_to_pay').': '.PublicSite::formatMoney($payment->charge_amount, 'IDR');
        }

        return implode("\n", [...$lines, $t('pay_before').": {$expiresAt}", '', '*'.$t('pay_securely', ['provider' => 'Midtrans']).'*', $paymentUrl, '', $t('booking_confirmed'), '', $t('need_help'), $t('security'), '', 'Tinggal Jalan']);
    }

    public function manualUrl(BookingPayment $payment): string
    {
        $phone = preg_replace('/\D+/', '', (string) $payment->booking?->whatsapp);
        return 'https://wa.me/'.$phone.'?text='.rawurlencode($this->text($payment));
    }
}