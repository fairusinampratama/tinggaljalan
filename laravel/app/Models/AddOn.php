<?php

namespace App\Models;

use App\Models\Concerns\HasTravelScopes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['slug', 'title', 'description', 'price_idr', 'price_usd', 'pricing_type', 'is_active'])]
class AddOn extends Model
{
    use HasTravelScopes;

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'description' => 'array',
            'price_idr' => 'integer',
            'price_usd' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function tourPackages(): BelongsToMany
    {
        return $this->belongsToMany(TourPackage::class);
    }
}
