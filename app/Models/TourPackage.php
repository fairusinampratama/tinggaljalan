<?php

namespace App\Models;

use App\Models\Concerns\HasTravelScopes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['destination_id', 'slug', 'title', 'category', 'tag', 'excerpt', 'intro', 'best_for', 'duration', 'difficulty', 'base_price_idr', 'base_price_usd', 'price_note', 'cover_image', 'cover_alt', 'gallery', 'pickup_areas', 'pickup_label', 'group_type', 'highlights', 'includes', 'excludes', 'notes', 'details', 'good_to_know', 'policies', 'testimonials', 'rating', 'review_count', 'review_source', 'styles', 'sort_order', 'is_featured', 'is_active'])]
class TourPackage extends Model
{
    use HasTravelScopes;

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'category' => 'array',
            'tag' => 'array',
            'excerpt' => 'array',
            'intro' => 'array',
            'best_for' => 'array',
            'difficulty' => 'array',
            'base_price_idr' => 'integer',
            'base_price_usd' => 'integer',
            'cover_alt' => 'array',
            'gallery' => 'array',
            'pickup_areas' => 'array',
            'pickup_label' => 'array',
            'group_type' => 'array',
            'highlights' => 'array',
            'includes' => 'array',
            'excludes' => 'array',
            'notes' => 'array',
            'details' => 'array',
            'good_to_know' => 'array',
            'policies' => 'array',
            'testimonials' => 'array',
            'rating' => 'decimal:2',
            'review_count' => 'integer',
            'review_source' => 'array',
            'styles' => 'array',
            'sort_order' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function itineraryItems(): HasMany
    {
        return $this->hasMany(ItineraryItem::class)->orderBy('day_number')->orderBy('sort_order');
    }

    public function packageAddOns(): HasMany
    {
        return $this->hasMany(PackageAddOn::class)->orderBy('sort_order')->orderBy('id');
    }

    public function newsArticles(): BelongsToMany
    {
        return $this->belongsToMany(NewsArticle::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
