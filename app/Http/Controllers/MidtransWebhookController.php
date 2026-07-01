<?php

namespace App\Http\Controllers;

use App\Payments\BookingPaymentService;
use Illuminate\Http\Request;

class MidtransWebhookController extends Controller
{
    public function __invoke(Request $request, BookingPaymentService $payments)
    {
        $payload = $request->all();

        if (! $payments->verifySignature($payload)) {
            abort(403);
        }

        $payments->handleNotification($payload);

        return response()->json(['ok' => true]);
    }
}
