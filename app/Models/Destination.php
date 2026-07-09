<?php

namespace App\Models;

use App\Models\Concerns\HasTravelScopes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name', 'region', 'province', 'short_description', 'cover_image', 'sort_order', 'is_featured', 'is_active'])]
class Destination extends Model
{
    use HasTravelScopes;

    protected function casts(): array
    {
        return [
            'short_description' => 'array',
            'sort_order' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function tourPackages(): HasMany
    {
        return $this->hasMany(TourPackage::class);
    }

    public function newsArticles(): HasMany
    {
        return $this->hasMany(NewsArticle::class);
    }



    public function packageAvailabilities(): HasMany
    {
        return $this->hasMany(PackageAvailability::class);
    }
}
