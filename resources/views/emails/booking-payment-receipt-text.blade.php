@php
    use App\Support\BookingLanguage;
    use App\Support\PublicSite;
    $booking = $payment->booking;
    $language = BookingLanguage::normalize($booking->communication_language);
    $packageTitle = PublicSite::localized($booking->tourPackage?->title, $language, $booking->booking_code);
    $paymentUrl = route('checkout.payment.show', $payment->public_token);
    $whatsappUrl = PublicSite::whatsappUrl([__('booking.support_question'), __('booking.booking_code').': '.$booking->booking_code]);
@endphp
Tinggal Jalan
{{ strtoupper(__('booking.payment_received')) }}

{{ __('booking.greeting', ['name' => $booking->name ?: __('booking.traveler')]) }}
{{ __('booking.receipt_intro', ['package' => $packageTitle]) }}
{{ __('booking.no_more_payment') }}

{{ strtoupper(__('booking.payment_summary')) }}
{{ __('booking.amount_paid') }}: {{ PublicSite::formatMoney($payment->charge_amount, 'IDR') }}
@if ($payment->quote_currency === 'USD')
{{ __('booking.original_quote') }}: {{ PublicSite::formatMoney($payment->quote_amount, 'USD') }}
{{ __('booking.exchange_rate') }}: USD 1 = {{ PublicSite::formatMoney($payment->exchange_rate, 'IDR') }}
@endif
{{ __('booking.paid_on') }}: {{ BookingLanguage::date($payment->paid_at?->timezone('Asia/Jakarta'), $language, true) }} WIB
{{ __('booking.payment_method') }}: {{ $payment->paymentMethodLabel() }}
{{ __('booking.order_reference') }}: {{ $payment->order_id }}

{{ strtoupper(__('booking.booking_summary')) }}
{{ __('booking.booking_code') }}: {{ $booking->booking_code }}
{{ __('booking.package') }}: {{ $packageTitle }}
{{ __('booking.travel_date') }}: {{ BookingLanguage::date($booking->travel_date, $language) }}
{{ __('booking.guests') }}: {{ $booking->pax ?: '-' }}
{{ __('booking.pickup') }}: {{ $booking->pickup ?: '-' }}

{{ strtoupper(__('booking.what_next')) }}
{{ __('booking.receipt_next') }}

{{ __('booking.view_status') }}:
{{ $paymentUrl }}

{{ __('booking.contact_support') }}:
{{ $whatsappUrl }}

{{ __('booking.receipt_note') }}

{{ __('booking.thank_you') }}
Tinggal Jalan