<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tour_package_id', 'day_number', 'time_label', 'title', 'description', 'sort_order'])]
class ItineraryItem extends Model
{
    protected function casts(): array
    {
        return [
            'day_number' => 'integer',
            'title' => 'array',
            'description' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function tourPackage(): BelongsTo
    {
        return $this->belongsTo(TourPackage::class);
    }
}
