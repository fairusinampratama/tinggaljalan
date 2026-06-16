<?php

namespace App\Models;

use App\Models\Concerns\HasTravelScopes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tour_package_id', 'destination_id', 'name', 'origin', 'rating', 'review_count', 'source', 'text', 'is_featured', 'is_active', 'sort_order'])]
class Review extends Model
{
    use HasTravelScopes;

    protected function casts(): array
    {
        return [
            'origin' => 'array',
            'rating' => 'decimal:2',
            'review_count' => 'integer',
            'source' => 'array',
            'text' => 'array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
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
