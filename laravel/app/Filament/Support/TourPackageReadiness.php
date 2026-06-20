<?php

namespace App\Filament\Support;

use App\Models\TourPackage;
use Illuminate\Database\Eloquent\Builder;

class TourPackageReadiness
{
    /**
     * @return array<int, string>
     */
    public static function missingItems(TourPackage $package): array
    {
        return collect([
            $package->destination_id ? null : 'Destination',
            filled($package->title['us'] ?? null) ? null : 'English title',
            filled($package->slug) ? null : 'Slug',
            filled($package->cover_image) ? null : 'Cover image',
            filled($package->duration) ? null : 'Duration',
            ($package->base_price_idr || $package->base_price_usd) ? null : 'Price',
            self::hasItinerary($package) ? null : 'Itinerary',
            self::hasEnglishList($package->highlights) ? null : 'Highlights',
            self::hasEnglishList($package->includes) ? null : 'Includes',
        ])
            ->filter()
            ->values()
            ->all();
    }

    public static function status(TourPackage $package): string
    {
        if (! $package->is_active) {
            return 'Draft';
        }

        return self::isReady($package) ? 'Ready' : 'Needs work';
    }

    public static function color(TourPackage $package): string
    {
        return match (self::status($package)) {
            'Ready' => 'success',
            'Draft' => 'gray',
            default => 'warning',
        };
    }

    public static function summary(TourPackage $package): string
    {
        $missing = self::missingItems($package);

        if ($missing === []) {
            return $package->is_active ? 'Ready' : 'Draft ready';
        }

        return implode(', ', $missing);
    }

    public static function isReady(TourPackage $package): bool
    {
        return self::missingItems($package) === [];
    }

    public static function applyNeedsAttention(Builder $query): Builder
    {
        return $query
            ->where('is_active', false)
            ->orWhereNull('destination_id')
            ->orWhereNull('slug')
            ->orWhere('slug', '')
            ->orWhereNull('cover_image')
            ->orWhere('cover_image', '')
            ->orWhereNull('duration')
            ->orWhere('duration', '')
            ->orWhere(function (Builder $query) {
                $query->whereNull('base_price_idr')->whereNull('base_price_usd');
            })
            ->orDoesntHave('itineraryItems')
            ->orWhere(function (Builder $query) {
                $query->whereNull('title->us')->orWhere('title->us', '');
            })
            ->orWhere(function (Builder $query) {
                $query->whereNull('highlights')
                    ->orWhereJsonLength('highlights', 0);
            })
            ->orWhere(function (Builder $query) {
                $query->whereNull('includes')
                    ->orWhereJsonLength('includes', 0);
            });
    }

    private static function hasItinerary(TourPackage $package): bool
    {
        if ($package->relationLoaded('itineraryItems')) {
            return $package->itineraryItems->isNotEmpty();
        }

        if (array_key_exists('itinerary_items_count', $package->getAttributes())) {
            return ((int) $package->itinerary_items_count) > 0;
        }

        return $package->itineraryItems()->exists();
    }

    private static function hasEnglishList(mixed $items): bool
    {
        if (is_array($items) && isset($items['us']) && is_array($items['us'])) {
            return collect($items['us'])->contains(fn ($item): bool => filled($item));
        }

        return collect($items ?? [])->contains(function ($item): bool {
            if (is_array($item)) {
                return filled($item['us'] ?? $item['id'] ?? $item['cn'] ?? null);
            }

            return filled($item);
        });
    }
}
