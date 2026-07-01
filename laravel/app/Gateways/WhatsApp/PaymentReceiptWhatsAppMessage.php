<?php

namespace App\Gateways\WhatsApp;

use App\Models\BookingPayment;
use App\Support\BookingLanguage;
use App\Support\PublicSite;

class PaymentReceiptWhatsAppMessage
{
    public function text(BookingPayment $payment): string
    {
        $payment->loadMissing('booking.tourPackage');
        $booking = $payment->booking;
        $language = BookingLanguage::normalize($booking?->communication_language);
        $package = PublicSite::localized($booking?->tourPackage?->title, $language, $booking?->booking_code ?? 'Tinggal Jalan');
        $customer = trim((string) $booking?->name) ?: BookingLanguage::translate('booking.traveler', [], $language);
        $paidAt = BookingLanguage::date($payment->paid_at?->timezone('Asia/Jakarta'), $language, true).' WIB';
        $t = fn (string $key, array $replace = []): string => BookingLanguage::translate("booking.{$key}", $replace, $language);

        return implode("\n", [$t('greeting', ['name' => $customer]), '', '*'.$t('payment_received').'*', '', $t('received_for', ['package' => "*{$package}*"]), '', $t('booking_code').": {$booking?->booking_code}", $t('amount_paid').': '.PublicSite::formatMoney($payment->charge_amount, 'IDR'), $t('paid_on').": {$paidAt}", '', $t('booking_secured'), '', $t('no_more_payment'), $t('need_help'), '', 'Tinggal Jalan']);
    }

    public function manualUrl(BookingPayment $payment): string
    {
        $phone = preg_replace('/\D+/', '', (string) $payment->booking?->whatsapp);
        return 'https://wa.me/'.$phone.'?text='.rawurlencode($this->text($payment));
    }
}