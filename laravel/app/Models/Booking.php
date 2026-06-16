<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['booking_code', 'tour_package_id', 'destination_id', 'name', 'email', 'whatsapp', 'travel_date', 'pax', 'pickup', 'traveler_type', 'currency', 'selected_add_ons', 'voucher_code', 'subtotal', 'discount_total', 'total', 'payment_gateway', 'notes', 'status'])]
class Booking extends Model
{
    protected function casts(): array
    {
        return [
            'travel_date' => 'date',
            'pax' => 'integer',
            'selected_add_ons' => 'array',
            'subtotal' => 'integer',
            'discount_total' => 'integer',
            'total' => 'integer',
        ];
    }

    public function tourPackage(): BelongsTo
    {
        return $this->belongsTo(TourPackage::class);
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }
}
