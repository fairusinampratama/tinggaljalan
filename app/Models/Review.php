<?php

namespace App\Models;

use App\Models\Concerns\HasTravelScopes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

#[Fillable(['name', 'origin', 'rating', 'review_count', 'source', 'text', 'is_featured', 'is_active', 'sort_order'])]
class Review extends Model
{
    use HasTravelScopes;

    public const MAX_ACTIVE_FEATURED = 3;

    protected static function booted(): void
    {
        static::saving(function (Review $review): void {
            if ($review->is_active && $review->is_featured && static::query()
                ->where('is_active', true)
                ->where('is_featured', true)
                ->when($review->exists, fn ($query) => $query->whereKeyNot($review->getKey()))
                ->count() >= self::MAX_ACTIVE_FEATURED) {
                throw ValidationException::withMessages([
                    'is_featured' => 'Only three active reviews can be featured. Unfeature another review first.',
                ]);
            }
        });
    }

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
}