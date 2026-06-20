<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tour_package_id', 'source_key', 'title', 'description', 'price_idr', 'price_usd', 'pricing_type', 'sort_order', 'is_active'])]
class PackageAddOn extends Model
{
    protected function casts(): array
    {
        return [
            'title' => 'array',
            'description' => 'array',
            'price_idr' => 'integer',
            'price_usd' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function tourPackage(): BelongsTo
    {
        return $this->belongsTo(TourPackage::class);
    }
}
