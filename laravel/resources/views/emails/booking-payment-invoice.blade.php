@php
    use App\Payments\PaymentSettingsService;
    use App\Support\PublicSite;
    use App\Support\BookingLanguage;

    $booking = $payment->booking;
    $language = BookingLanguage::normalize($booking->communication_language);
    $paymentSettings = app(PaymentSettingsService::class);
    $packageTitle = PublicSite::localized($booking->tourPackage?->title, $language, $booking->booking_code);
    $paymentUrl = route('checkout.payment.show', $payment->public_token);
    $whatsappUrl = PublicSite::whatsappUrl([
        __('booking.support_question'),
        __('booking.booking_code').': '.$booking->booking_code,
    ]);
    $addOns = collect($booking->selected_add_ons ?? [])
        ->map(function (array $addOn) use ($language): string {
            $title = PublicSite::localized($addOn['title'] ?? [], $language, $addOn['slug'] ?? 'Add-on');
            $pricing = str($addOn['pricing_type'] ?? 'per_booking')->replace('_', ' ')->toString();

            return "{$title} ({$pricing})";
        })
        ->values();
@endphp

<span style="display:none!important; visibility:hidden; opacity:0; color:transparent; height:0; width:0; overflow:hidden; mso-hide:all;">
    {{ __('booking.preheader_invoice', ['provider' => $paymentSettings->publicLabel()]) }}
</span>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0; padding:0; background:#f4f6f8; font-family:Arial, Helvetica, sans-serif; color:#111827;">
    <tr>
        <td align="center" style="padding:28px 16px;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; max-width:640px; background:#ffffff; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;">
                <tr>
                    <td style="padding:24px 28px; background:#111827;">
                        <p style="margin:0; color:#ffffff; font-size:22px; line-height:1.2; font-weight:700;">Tinggal Jalan</p>
                        <p style="margin:8px 0 0; color:#d1d5db; font-size:13px; line-height:1.5;">{{ __('booking.invoice_label') }}</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:22px 28px; background:#fff7ed; border-bottom:1px solid #fed7aa;">
                        <p style="margin:0 0 6px; color:#9a3412; font-size:12px; line-height:1.4; font-weight:700; letter-spacing:.04em; text-transform:uppercase;">{{ __('booking.payment_required') }}</p>
                        <h1 style="margin:0; color:#111827; font-size:24px; line-height:1.3; font-weight:700;">{{ __('booking.pay_securely', ['provider' => $paymentSettings->publicLabel()]) }}</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px;">
                        <p style="margin:0 0 14px; font-size:16px; line-height:1.6;">{{ __('booking.greeting', ['name' => $booking->name ?: __('booking.traveler')]) }}</p>
                        <p style="margin:0 0 22px; font-size:15px; line-height:1.7; color:#374151;">{{ __('booking.invoice_intro', ['provider' => $paymentSettings->publicLabel()]) }}</p>

                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 22px;">
                            <tr>
                                <td style="border-radius:8px; background:#ef7d58;">
                                    <a href="{{ $paymentUrl }}" style="display:inline-block; padding:13px 20px; color:#111827; font-size:15px; line-height:1.4; font-weight:700; text-decoration:none;">{{ __('booking.pay_securely', ['provider' => $paymentSettings->publicLabel()]) }}</a>
                                </td>
                            </tr>
                        </table>
                        <p style="margin:0 0 26px; font-size:13px; line-height:1.6; color:#6b7280;">{{ __('booking.button_fallback') }}<br><a href="{{ $paymentUrl }}" style="color:#ef7d58; word-break:break-all;">{{ $paymentUrl }}</a></p>

                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 18px; border:1px solid #e5e7eb; border-radius:8px;">
                            <tr>
                                <td style="padding:18px;">
                                    <p style="margin:0 0 12px; color:#6b7280; font-size:12px; line-height:1.4; font-weight:700; letter-spacing:.04em; text-transform:uppercase;">{{ __('booking.booking_summary') }}</p>
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                        <tr><td style="padding:5px 0; color:#6b7280; font-size:14px;">{{ __('booking.booking_code') }}</td><td align="right" style="padding:5px 0; color:#111827; font-size:14px; font-weight:700;">{{ $booking->booking_code }}</td></tr>
                                        <tr><td style="padding:5px 0; color:#6b7280; font-size:14px;">{{ __('booking.package') }}</td><td align="right" style="padding:5px 0; color:#111827; font-size:14px; font-weight:700;">{{ $packageTitle }}</td></tr>
                                        <tr><td style="padding:5px 0; color:#6b7280; font-size:14px;">{{ __('booking.travel_date') }}</td><td align="right" style="padding:5px 0; color:#111827; font-size:14px;">{{ BookingLanguage::date($booking->travel_date, $language) }}</td></tr>
                                        <tr><td style="padding:5px 0; color:#6b7280; font-size:14px;">{{ __('booking.guests') }}</td><td align="right" style="padding:5px 0; color:#111827; font-size:14px;">{{ $booking->pax ?: '-' }}</td></tr>
                                        <tr><td style="padding:5px 0; color:#6b7280; font-size:14px;">{{ __('booking.pickup') }}</td><td align="right" style="padding:5px 0; color:#111827; font-size:14px;">{{ $booking->pickup ?: '-' }}</td></tr>
                                        <tr><td style="padding:5px 0; color:#6b7280; font-size:14px;">{{ __('booking.traveler_type') }}</td><td align="right" style="padding:5px 0; color:#111827; font-size:14px;">{{ $booking->traveler_type ?: '-' }}</td></tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 18px; border:1px solid #fed7aa; border-radius:8px; background:#fff7ed;">
                            <tr>
                                <td style="padding:18px;">
                                    <p style="margin:0 0 12px; color:#9a3412; font-size:12px; line-height:1.4; font-weight:700; letter-spacing:.04em; text-transform:uppercase;">{{ __('booking.payment_summary') }}</p>
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                        <tr><td style="padding:5px 0; color:#7c2d12; font-size:14px;">{{ __('booking.original_quote') }}</td><td align="right" style="padding:5px 0; color:#111827; font-size:14px; font-weight:700;">{{ PublicSite::formatMoney($payment->quote_amount, $payment->quote_currency) }}</td></tr>
                                        @if ($booking->voucher_code)
                                            <tr><td style="padding:5px 0; color:#7c2d12; font-size:14px;">{{ __('booking.voucher') }}</td><td align="right" style="padding:5px 0; color:#111827; font-size:14px;">{{ $booking->voucher_code }}</td></tr>
                                        @endif
                                        @if ((int) $booking->discount_total > 0)
                                            <tr><td style="padding:5px 0; color:#7c2d12; font-size:14px;">{{ __('booking.discount') }}</td><td align="right" style="padding:5px 0; color:#111827; font-size:14px;">-{{ PublicSite::formatMoney($booking->discount_total, $payment->quote_currency) }}</td></tr>
                                        @endif
                                        @if ($payment->quote_currency === 'USD')
                                            <tr><td style="padding:5px 0; color:#7c2d12; font-size:14px;">{{ __('booking.exchange_rate') }}</td><td align="right" style="padding:5px 0; color:#111827; font-size:14px;">1 USD = {{ PublicSite::formatMoney($payment->exchange_rate, 'IDR') }}</td></tr>
                                        @endif
                                        <tr><td style="padding:8px 0 5px; color:#7c2d12; font-size:15px; font-weight:700;">{{ __('booking.charge_label', ['provider' => $paymentSettings->publicLabel()]) }}</td><td align="right" style="padding:8px 0 5px; color:#111827; font-size:18px; font-weight:700;">{{ PublicSite::formatMoney($payment->charge_amount, 'IDR') }}</td></tr>
                                        <tr><td style="padding:5px 0; color:#7c2d12; font-size:14px;">{{ __('booking.payment_expires') }}</td><td align="right" style="padding:5px 0; color:#111827; font-size:14px;">{{ optional($payment->expires_at)->locale(BookingLanguage::locale($language))->translatedFormat('j M Y, H:i') ?: '-' }}</td></tr>
                                    </table>
                                    <p style="margin:12px 0 0; font-size:13px; line-height:1.6; color:#7c2d12;">
                                        @if ($payment->quote_currency === 'USD')
                                            {{ __('booking.usd_explanation', ['provider' => $paymentSettings->publicLabel()]) }}
                                        @else
                                            {{ __('booking.idr_explanation', ['provider' => $paymentSettings->publicLabel()]) }}
                                        @endif
                                    </p>
                                </td>
                            </tr>
                        </table>

                        @if ($addOns->isNotEmpty())
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 18px; border:1px solid #e5e7eb; border-radius:8px;">
                                <tr>
                                    <td style="padding:18px;">
                                        <p style="margin:0 0 10px; color:#6b7280; font-size:12px; line-height:1.4; font-weight:700; letter-spacing:.04em; text-transform:uppercase;">{{ __('booking.add_ons') }}</p>
                                        @foreach ($addOns as $addOn)
                                            <p style="margin:0 0 6px; color:#111827; font-size:14px; line-height:1.5;">{{ $addOn }}</p>
                                        @endforeach
                                    </td>
                                </tr>
                            </table>
                        @endif

                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 22px; border:1px solid #e5e7eb; border-radius:8px;">
                            <tr>
                                <td style="padding:18px;">
                                    <p style="margin:0 0 12px; color:#6b7280; font-size:12px; line-height:1.4; font-weight:700; letter-spacing:.04em; text-transform:uppercase;">{{ __('booking.what_next') }}</p>
                                    <p style="margin:0; color:#374151; font-size:14px; line-height:1.7;">{!! nl2br(e(__('booking.invoice_next', ['provider' => $paymentSettings->publicLabel()]))) !!}</p>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:0 0 8px; font-size:15px; line-height:1.6; color:#374151;">{{ __('booking.support_question') }}</p>
                        <p style="margin:0 0 22px; font-size:14px; line-height:1.6;"><a href="{{ $whatsappUrl }}" style="color:#ef7d58; font-weight:700;">{{ __('booking.contact_whatsapp') }}</a><br><span style="color:#6b7280; word-break:break-all;">{{ $whatsappUrl }}</span></p>

                        <p style="margin:0; font-size:13px; line-height:1.6; color:#6b7280;">{{ __('booking.payment_reference', ['reference' => $payment->order_id]) }}<br>{{ __('booking.secure_link_note') }}</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:18px 28px; background:#f9fafb; border-top:1px solid #e5e7eb;">
                        <p style="margin:0; color:#6b7280; font-size:13px; line-height:1.6;">{{ __('booking.thank_you') }}<br><strong style="color:#111827;">Tinggal Jalan</strong></p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>