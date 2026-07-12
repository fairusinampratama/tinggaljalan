<?php

namespace App\Http\Controllers;

use App\Payments\BookingPaymentService;
use Illuminate\Http\Request;

class DokuWebhookController extends Controller
{
    public function __invoke(Request $request, BookingPaymentService $payments)
    {
        if (! $payments->verifyDokuSignature($request)) {
            abort(403);
        }

        $payments->handleDokuNotification($request->all());

        return response()->json(['ok' => true]);
    }
}
