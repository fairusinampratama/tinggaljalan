<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['booking_code', 'tour_package_id', 'destination_id', 'name', 'email', 'whatsapp', 'whatsapp_country', 'communication_language', 'travel_date', 'pax', 'pickup', 'traveler_type', 'currency', 'selected_add_ons', 'voucher_code', 'subtotal', 'discount_total', 'total', 'payment_gateway', 'notes', 'status', 'confirmed_at', 'cancelled_at', 'completed_at'])]
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
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
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

    public function payments(): HasMany
    {
        return $this->hasMany(BookingPayment::class);
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(BookingPayment::class)->latestOfMany();
    }

    public function activePayment(): HasOne
    {
        return $this->hasOne(BookingPayment::class)
            ->whereIn('status', ['pending', 'invoice_sent', 'paid'])
            ->latestOfMany();
    }
}
