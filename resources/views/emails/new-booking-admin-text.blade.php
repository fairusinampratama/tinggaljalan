@php
    use App\Support\BookingLanguage;
    use App\Support\PublicSite;

    $booking->loadMissing('tourPackage.destination');
    $language = BookingLanguage::normalize($booking->communication_language);
    $packageTitle = PublicSite::localized($booking->tourPackage?->title, $language, $booking->booking_code);
    $total = $booking->pricing_status === 'quote_required'
        ? 'To be confirmed'
        : PublicSite::formatMoney($booking->total, $booking->currency ?: 'IDR');
    $adminUrl = url('/admin/bookings?tableSearch='.rawurlencode((string) $booking->booking_code));
@endphp
New booking received

Code: {{ $booking->booking_code }}
Customer: {{ $booking->name }}
WhatsApp: {{ $booking->whatsapp ?: '-' }}
Email: {{ $booking->email ?: '-' }}
Package: {{ $packageTitle }}
Travel date: {{ BookingLanguage::date($booking->travel_date, $language) }}
Guests: {{ $booking->pax }}
Pickup: {{ $booking->pickup ?: '-' }}
Total: {{ $total }}
@if ($booking->notes)
Notes: {{ $booking->notes }}
@endif

Review booking: {{ $adminUrl }}