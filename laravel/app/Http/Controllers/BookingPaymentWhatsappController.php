<?php

namespace App\Http\Controllers;

use App\Gateways\WhatsApp\WhatsAppGatewayService;
use App\Models\BookingPayment;
use Illuminate\Http\RedirectResponse;

class BookingPaymentWhatsappController
{
    public function __invoke(BookingPayment $payment, WhatsAppGatewayService $whatsApp): RedirectResponse
    {
        abort_unless(auth()->user()?->is_admin, 403);

        $result = $whatsApp->sendPaymentRequest($payment);

        if ($result->manualFallback && $result->redirectUrl) {
            return redirect()->away($result->redirectUrl);
        }

        return redirect()->to('/admin/bookings');
    }
}