<?php

namespace App\Gateways\WhatsApp;

use App\Models\Booking;
use App\Support\BookingLanguage;
use App\Support\PublicSite;

class NewBookingAdminWhatsAppMessage
{
    public function text(Booking $booking): string
    {
        $booking->loadMissing('tourPackage.destination');

        $language = BookingLanguage::normalize($booking->communication_language);
        $package = PublicSite::localized($booking->tourPackage?->title, $language, $booking->booking_code);
        $total = $booking->pricing_status === 'quote_required'
            ? 'To be confirmed'
            : PublicSite::formatMoney($booking->total, $booking->currency ?: 'IDR');

        return implode("\n", array_filter([
            '*New booking received*',
            '',
            "Code: {$booking->booking_code}",
            "Name: {$booking->name}",
            "Package: {$package}",
            'Date: '.BookingLanguage::date($booking->travel_date, $language),
            "Guests: {$booking->pax}",
            'Pickup: '.($booking->pickup ?: '-'),
            "Total: {$total}",
            $booking->notes ? 'Notes: '.str($booking->notes)->limit(180)->toString() : null,
            '',
            url('/admin/bookings?tableSearch='.rawurlencode((string) $booking->booking_code)),
        ], fn (?string $line): bool => $line !== null));
    }
}