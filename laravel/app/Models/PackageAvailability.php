<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tour_package_id', 'destination_id', 'date', 'status', 'seats_left', 'reason', 'notes'])]
class PackageAvailability extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'seats_left' => 'integer',
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
