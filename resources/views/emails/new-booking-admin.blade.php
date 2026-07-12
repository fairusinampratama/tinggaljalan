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

<span style="display:none!important; visibility:hidden; opacity:0; color:transparent; height:0; width:0; overflow:hidden; mso-hide:all;">
    New booking {{ $booking->booking_code }} from {{ $booking->name }}.
</span>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0; padding:0; background:#f5f7f6; font-family:Arial, Helvetica, sans-serif; color:#172126;">
    <tr>
        <td align="center" style="padding:28px 16px;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; max-width:640px; background:#ffffff; border:1px solid #e3e8e6; border-radius:8px; overflow:hidden;">
                <tr>
                    <td style="padding:24px 28px; background:#172126;">
                        <p style="margin:0; color:#ffffff; font-size:22px; line-height:1.2; font-weight:700;">Tinggal Jalan</p>
                        <p style="margin:8px 0 0; color:#e3e8e6; font-size:13px; line-height:1.5;">New booking request</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px;">
                        <h1 style="margin:0 0 18px; color:#172126; font-size:24px; line-height:1.3; font-weight:700;">New booking {{ $booking->booking_code }}</h1>
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 22px; border:1px solid #e3e8e6; border-radius:8px;">
                            <tr>
                                <td style="padding:18px;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                        <tr><td style="padding:6px 0; color:#667277; font-size:14px;">Customer</td><td align="right" style="padding:6px 0; color:#172126; font-size:14px; font-weight:700;">{{ $booking->name }}</td></tr>
                                        <tr><td style="padding:6px 0; color:#667277; font-size:14px;">WhatsApp</td><td align="right" style="padding:6px 0; color:#172126; font-size:14px;">{{ $booking->whatsapp ?: '-' }}</td></tr>
                                        <tr><td style="padding:6px 0; color:#667277; font-size:14px;">Email</td><td align="right" style="padding:6px 0; color:#172126; font-size:14px;">{{ $booking->email ?: '-' }}</td></tr>
                                        <tr><td style="padding:6px 0; color:#667277; font-size:14px;">Package</td><td align="right" style="padding:6px 0; color:#172126; font-size:14px; font-weight:700;">{{ $packageTitle }}</td></tr>
                                        <tr><td style="padding:6px 0; color:#667277; font-size:14px;">Travel date</td><td align="right" style="padding:6px 0; color:#172126; font-size:14px;">{{ BookingLanguage::date($booking->travel_date, $language) }}</td></tr>
                                        <tr><td style="padding:6px 0; color:#667277; font-size:14px;">Guests</td><td align="right" style="padding:6px 0; color:#172126; font-size:14px;">{{ $booking->pax }}</td></tr>
                                        <tr><td style="padding:6px 0; color:#667277; font-size:14px;">Pickup</td><td align="right" style="padding:6px 0; color:#172126; font-size:14px;">{{ $booking->pickup ?: '-' }}</td></tr>
                                        <tr><td style="padding:8px 0 6px; color:#667277; font-size:15px; font-weight:700;">Total</td><td align="right" style="padding:8px 0 6px; color:#172126; font-size:18px; font-weight:700;">{{ $total }}</td></tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        @if ($booking->notes)
                            <p style="margin:0 0 22px; padding:14px 16px; background:#f5f7f6; border-radius:8px; color:#172126; font-size:14px; line-height:1.6;"><strong>Notes:</strong> {{ $booking->notes }}</p>
                        @endif

                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td style="border-radius:8px; background:#102a36;">
                                    <a href="{{ $adminUrl }}" style="display:inline-block; padding:13px 20px; color:#ffffff; font-size:15px; line-height:1.4; font-weight:700; text-decoration:none;">Review booking</a>
                                </td>
                            </tr>
                        </table>
                        <p style="margin:18px 0 0; font-size:13px; line-height:1.6; color:#667277; word-break:break-all;">{{ $adminUrl }}</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>