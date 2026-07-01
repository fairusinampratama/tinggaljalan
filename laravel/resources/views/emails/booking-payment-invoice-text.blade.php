@php
    use App\Payments\PaymentSettingsService;
    use App\Support\BookingLanguage;
    use App\Support\PublicSite;
    $booking = $payment->booking;
    $language = BookingLanguage::normalize($booking->communication_language);
    $paymentSettings = app(PaymentSettingsService::class);
    $packageTitle = PublicSite::localized($booking->tourPackage?->title, $language, $booking->booking_code);
    $paymentUrl = route('checkout.payment.show', $payment->public_token);
    $addOns = collect($booking->selected_add_ons ?? [])->map(
        fn (array $addOn): string => PublicSite::localized($addOn['title'] ?? [], $language, $addOn['slug'] ?? __('booking.add_ons')),
    );
    $whatsappUrl = PublicSite::whatsappUrl([__('booking.support_question'), __('booking.booking_code').': '.$booking->booking_code]);
@endphp
Tinggal Jalan
{{ __('booking.payment_required') }}

{{ __('booking.greeting', ['name' => $booking->name ?: __('booking.traveler')]) }}
{{ __('booking.invoice_intro', ['provider' => $paymentSettings->publicLabel()]) }}

{{ __('booking.pay_securely', ['provider' => $paymentSettings->publicLabel()]) }}:
{{ $paymentUrl }}

{{ strtoupper(__('booking.booking_summary')) }}
{{ __('booking.booking_code') }}: {{ $booking->booking_code }}
{{ __('booking.package') }}: {{ $packageTitle }}
{{ __('booking.travel_date') }}: {{ BookingLanguage::date($booking->travel_date, $language) }}
{{ __('booking.guests') }}: {{ $booking->pax ?: '-' }}
{{ __('booking.pickup') }}: {{ $booking->pickup ?: '-' }}
{{ __('booking.traveler_type') }}: {{ $booking->traveler_type ?: '-' }}

{{ strtoupper(__('booking.payment_summary')) }}
{{ __('booking.original_quote') }}: {{ PublicSite::formatMoney($payment->quote_amount, $payment->quote_currency) }}
@if ($payment->quote_currency === 'USD')
{{ __('booking.exchange_rate') }}: 1 USD = {{ PublicSite::formatMoney($payment->exchange_rate, 'IDR') }}
@endif
{{ __('booking.charge_label', ['provider' => $paymentSettings->publicLabel()]) }}: {{ PublicSite::formatMoney($payment->charge_amount, 'IDR') }}
{{ __('booking.payment_expires') }}: {{ BookingLanguage::date($payment->expires_at?->timezone('Asia/Jakarta'), $language, true) }} WIB

@if ($addOns->isNotEmpty())
{{ strtoupper(__('booking.add_ons')) }}
@foreach ($addOns as $addOn)
- {{ $addOn }}
@endforeach
@endif

{{ strtoupper(__('booking.what_next')) }}
{{ __('booking.invoice_next', ['provider' => $paymentSettings->publicLabel()]) }}

{{ __('booking.contact_whatsapp') }}:
{{ $whatsappUrl }}

{{ __('booking.payment_reference', ['reference' => $payment->order_id]) }}
{{ __('booking.secure_link_note') }}

{{ __('booking.thank_you') }}
Tinggal Jalan