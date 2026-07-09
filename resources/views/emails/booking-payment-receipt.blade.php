@php
    use App\Support\PublicSite;
    use App\Support\BookingLanguage;

    $booking = $payment->booking;
    $language = BookingLanguage::normalize($booking->communication_language);
    $packageTitle = PublicSite::localized($booking->tourPackage?->title, $language, $booking->booking_code);
    $paymentUrl = route('checkout.payment.show', $payment->public_token);
    $paidAt = optional($payment->paid_at)->timezone('Asia/Jakarta')->locale(BookingLanguage::locale($language))->translatedFormat('j M Y, H:i').' WIB';
    $whatsappUrl = PublicSite::whatsappUrl([
        __('booking.support_question'),
        __('booking.booking_code').': '.$booking->booking_code,
    ]);
@endphp
<span style="display:none!important;visibility:hidden;opacity:0;height:0;width:0;overflow:hidden;">{{ __('booking.preheader_receipt') }}</span>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0;padding:0;background:#f5f7f6;font-family:Arial,Helvetica,sans-serif;color:#172126;">
<tr><td align="center" style="padding:28px 16px;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:640px;background:#ffffff;border:1px solid #e3e8e6;border-radius:8px;overflow:hidden;">
<tr><td style="padding:24px 28px;background:#102a36;color:#ffffff;"><p style="margin:0;font-size:22px;font-weight:700;">Tinggal Jalan<span style="display:none; visibility:hidden; mso-hide:all; font-size:0; color:transparent; line-height:0;">{{ microtime(true) }}</span></p><p style="margin:8px 0 0;color:#e3e8e6;font-size:13px;">{{ __('booking.receipt_label') }}</p></td></tr>
<tr><td style="padding:22px 28px;background:#ecfdf5;border-bottom:1px solid #a7f3d0;"><p style="margin:0 0 6px;color:#047857;font-size:12px;font-weight:700;text-transform:uppercase;">{{ __('booking.payment_received') }}</p><h1 style="margin:0;color:#065f46;font-size:28px;">{{ PublicSite::formatMoney($payment->charge_amount, 'IDR') }}</h1><p style="margin:8px 0 0;color:#047857;font-size:14px;">{{ __('booking.no_more_payment') }}</p></td></tr>
<tr><td style="padding:28px;">
<p style="margin:0 0 12px;font-size:16px;">{{ __('booking.greeting', ['name' => $booking->name ?: __('booking.traveler')]) }}</p>
<p style="margin:0 0 22px;color:#667277;line-height:1.7;">{{ __('booking.receipt_intro', ['package' => $packageTitle]) }}</p>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:18px;border:1px solid #d1fae5;border-radius:8px;background:#f0fdf4;"><tr><td style="padding:18px;">
<p style="margin:0 0 12px;color:#047857;font-size:12px;font-weight:700;text-transform:uppercase;">{{ __('booking.payment_summary') }}</p>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
<tr><td style="padding:5px 0;color:#667277;">{{ __('booking.amount_paid') }}</td><td align="right" style="padding:5px 0;font-weight:700;">{{ PublicSite::formatMoney($payment->charge_amount, 'IDR') }}</td></tr>
@if ($payment->quote_currency === 'USD')
<tr><td style="padding:5px 0;color:#667277;">{{ __('booking.original_quote') }}</td><td align="right" style="padding:5px 0;">{{ PublicSite::formatMoney($payment->quote_amount, 'USD') }}</td></tr>
<tr><td style="padding:5px 0;color:#667277;">{{ __('booking.exchange_rate') }}</td><td align="right" style="padding:5px 0;">USD 1 = {{ PublicSite::formatMoney($payment->exchange_rate, 'IDR') }}</td></tr>
@endif
<tr><td style="padding:5px 0;color:#667277;">{{ __('booking.paid_on') }}</td><td align="right" style="padding:5px 0;">{{ $paidAt ?: '-' }}</td></tr>
<tr><td style="padding:5px 0;color:#667277;">{{ __('booking.payment_method') }}</td><td align="right" style="padding:5px 0;">{{ $payment->provider === 'manual' ? 'Manual Bank Transfer' : ($payment->midtrans_payment_type ?: '-') }}</td></tr>
<tr><td style="padding:5px 0;color:#667277;">{{ __('booking.transaction_reference') }}</td><td align="right" style="padding:5px 0;word-break:break-all;">{{ $payment->provider === 'manual' ? '-' : ($payment->midtrans_transaction_id ?: '-') }}</td></tr>
<tr><td style="padding:5px 0;color:#667277;">{{ __('booking.order_reference') }}</td><td align="right" style="padding:5px 0;word-break:break-all;">{{ $payment->order_id }}</td></tr>
</table></td></tr></table>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:18px;border:1px solid #e3e8e6;border-radius:8px;"><tr><td style="padding:18px;">
<p style="margin:0 0 12px;color:#667277;font-size:12px;font-weight:700;text-transform:uppercase;">{{ __('booking.booking_summary') }}</p>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
<tr><td style="padding:5px 0;color:#667277;">{{ __('booking.booking_code') }}</td><td align="right" style="padding:5px 0;font-weight:700;">{{ $booking->booking_code }}</td></tr>
<tr><td style="padding:5px 0;color:#667277;">{{ __('booking.package') }}</td><td align="right" style="padding:5px 0;font-weight:700;">{{ $packageTitle }}</td></tr>
<tr><td style="padding:5px 0;color:#667277;">{{ __('booking.travel_date') }}</td><td align="right" style="padding:5px 0;">{{ BookingLanguage::date($booking->travel_date, $language) }}</td></tr>
<tr><td style="padding:5px 0;color:#667277;">{{ __('booking.guests') }}</td><td align="right" style="padding:5px 0;">{{ $booking->pax ?: '-' }}</td></tr>
<tr><td style="padding:5px 0;color:#667277;">{{ __('booking.pickup') }}</td><td align="right" style="padding:5px 0;">{{ $booking->pickup ?: '-' }}</td></tr>
</table></td></tr></table>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:20px;background:#f5f7f6;border-radius:8px;"><tr><td style="padding:18px;"><p style="margin:0 0 10px;font-weight:700;">{{ __('booking.what_next') }}</p><p style="margin:0;color:#667277;line-height:1.7;">{!! nl2br(e(__('booking.receipt_next'))) !!}</p></td></tr></table>
<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 22px;"><tr><td style="border-radius:8px; background:#102a36;"><a href="{{ $paymentUrl }}" style="display:inline-block; padding:13px 20px; color:#ffffff; font-size:15px; line-height:1.4; font-weight:700; text-decoration:none;">{{ __('booking.view_status') }}</a></td></tr></table>
<p style="margin:0 0 22px;"><a href="{{ $whatsappUrl }}" style="color:#047857;font-weight:700;">{{ __('booking.contact_support') }}</a></p>
<p style="margin:0;color:#667277;font-size:12px;line-height:1.6;">{{ __('booking.receipt_note') }}</p>
</td></tr>
<tr><td style="padding:18px 28px;background:#f5f7f6;color:#667277;font-size:12px;">Tinggal Jalan &middot; {{ __('booking.payment_reference', ['reference' => $payment->order_id]) }}</td></tr>
</table></td></tr></table>
