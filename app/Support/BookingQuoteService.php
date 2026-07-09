<?php

namespace App\Support;

use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingQuoteService
{
    public function apply(Booking $booking, int $unitPrice): Booking
    {
        if ($booking->pricing_status !== 'quote_required') {
            throw ValidationException::withMessages(['unit_price' => 'Only quote-required bookings can receive a manual quote.']);
        }

        return DB::transaction(function () use ($booking, $unitPrice) {
            $pax = max(1, $booking->pax);
            $packageSubtotal = $unitPrice * $pax;
            $addOnTotal = collect($booking->selected_add_ons ?? [])->sum(function (array $item) use ($booking, $pax) {
                $price = $booking->currency === 'USD' ? (int) ($item['price_usd'] ?? 0) : (int) ($item['price_idr'] ?? 0);

                return ($item['pricing_type'] ?? 'per_booking') === 'per_pax' ? $price * $pax : $price;
            });
            $subtotal = $packageSubtotal + $addOnTotal;
            $voucher = PublicSite::activeVoucher($booking->voucher_code, $booking->currency);
            $discount = $voucher
                ? ($voucher->discount_type === 'percent'
                    ? (int) floor($subtotal * ((float) $voucher->discount_value / 100))
                    : (int) $voucher->discount_value)
                : 0;

            $booking->update([
                'pricing_status' => 'quoted',
                'unit_price' => $unitPrice,
                'package_subtotal' => $packageSubtotal,
                'subtotal' => $subtotal,
                'discount_total' => $discount,
                'total' => max(0, $subtotal - $discount),
                'quoted_at' => now(),
            ]);

            return $booking->refresh();
        });
    }
}
