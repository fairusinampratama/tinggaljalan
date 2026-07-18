<?php

namespace App\Filament\Support;

use App\Models\PackagePriceTier;
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
            self::hasValidPricing($package) ? null : 'Pricing',
            self::hasItinerary($package) ? null : 'Itinerary',
            self::hasEnglishList($package->highlights) ? null : 'Highlights',
            self::hasEnglishList($package->includes) ? null : 'Includes',
        ])
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Evaluate the complete Filament form state before relationship repeaters are saved.
     *
     * @param  array<string, mixed>  $state
     * @return array<int, string>
     */
    public static function missingItemsFromState(array $state): array
    {
        return collect([
            filled($state['destination_id'] ?? null) ? null : 'Destination',
            filled($state['title']['us'] ?? null) ? null : 'English title',
            filled($state['slug'] ?? null) ? null : 'Slug',
            filled($state['cover_image'] ?? null) ? null : 'Cover image',
            filled($state['duration'] ?? null) ? null : 'Duration',
            self::hasValidPricingState($state) ? null : 'Pricing',
            self::hasItineraryState($state['itineraryItems'] ?? []) ? null : 'Itinerary',
            self::hasEnglishList($state['highlights'] ?? []) ? null : 'Highlights',
            self::hasEnglishList($state['includes'] ?? []) ? null : 'Includes',
        ])->filter()->values()->all();
    }

    public static function status(TourPackage $package): string
    {
        return self::isReady($package) ? 'Complete' : 'Incomplete';
    }

    public static function color(TourPackage $package): string
    {
        return match (self::status($package)) {
            'Complete' => 'success',
            default => 'warning',
        };
    }

    public static function summary(TourPackage $package): string
    {
        $missing = self::missingItems($package);

        if ($missing === []) {
            return "\u{2014}";
        }

        return implode(', ', $missing);
    }

    public static function isReady(TourPackage $package): bool
    {
        return self::missingItems($package) === [];
    }

    public static function applyIncomplete(Builder $query): Builder
    {
        return $query
            ->whereNull('destination_id')
            ->orWhereNull('slug')
            ->orWhere('slug', '')
            ->orWhereNull('cover_image')
            ->orWhere('cover_image', '')
            ->orWhereNull('duration')
            ->orWhere('duration', '')
            ->orWhere(function (Builder $query) {
                $query
                    ->where(function (Builder $query) {
                        $query->whereNull('pricing_mode')->orWhere('pricing_mode', 'flat');
                    })
                    ->where(function (Builder $query) {
                        $query->whereNull('base_price_idr')
                            ->orWhere('base_price_idr', '<=', 0)
                            ->orWhereNull('base_price_usd')
                            ->orWhere('base_price_usd', '<=', 0);
                    });
            })
            ->orWhere(function (Builder $query) {
                $query->where('pricing_mode', 'tiered')
                    ->where(function (Builder $query) {
                        $query->whereDoesntHave('priceTiers')
                            ->orWhereHas('priceTiers', function (Builder $query) {
                                $query->whereNull('price_idr')
                                    ->orWhere('price_idr', '<=', 0)
                                    ->orWhereNull('price_usd')
                                    ->orWhere('price_usd', '<=', 0);
                            });
                    });
            })
            ->orWhereDoesntHave('itineraryItems', function (Builder $query) {
                $query->whereNotNull('title->us')->where('title->us', '!=', '');
            })
            ->orWhere(function (Builder $query) {
                $query->whereNull('title->us')->orWhere('title->us', '');
            })
            ->orWhere(fn (Builder $query) => self::applyMissingList($query, 'highlights'))
            ->orWhere(fn (Builder $query) => self::applyMissingList($query, 'includes'));
    }

    private static function hasItinerary(TourPackage $package): bool
    {
        if ($package->relationLoaded('itineraryItems')) {
            return $package->itineraryItems->contains(
                fn ($item): bool => filled($item->title['us'] ?? null),
            );
        }

        return $package->itineraryItems()
            ->whereNotNull('title->us')
            ->where('title->us', '!=', '')
            ->exists();
    }

    private static function hasValidPricing(TourPackage $package): bool
    {
        if ($package->pricing_mode !== 'tiered') {
            return (int) $package->base_price_idr > 0
                && (int) $package->base_price_usd > 0;
        }

        $tiers = $package->relationLoaded('priceTiers')
            ? $package->priceTiers
            : $package->priceTiers()->get();

        return PackagePriceTier::validateRanges($tiers->map->attributesToArray()->all()) === null;
    }

    /** @param array<string, mixed> $state */
    private static function hasValidPricingState(array $state): bool
    {
        if (($state['pricing_mode'] ?? 'flat') !== 'tiered') {
            return (int) ($state['base_price_idr'] ?? 0) > 0
                && (int) ($state['base_price_usd'] ?? 0) > 0;
        }

        return PackagePriceTier::validateRanges(array_values((array) ($state['priceTiers'] ?? []))) === null;
    }

    private static function hasItineraryState(mixed $items): bool
    {
        return collect($items ?? [])->contains(
            fn ($item): bool => is_array($item) && filled($item['title']['us'] ?? null),
        );
    }

    private static function applyMissingList(Builder $query, string $column): Builder
    {
        $driver = $query->getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            return $query
                ->whereNull($column)
                ->orWhereRaw(
                    "(JSON_TYPE(`{$column}`) = 'ARRAY' AND JSON_LENGTH(`{$column}`) = 0)",
                )
                ->orWhereRaw(
                    "(JSON_TYPE(`{$column}`) = 'OBJECT' AND (JSON_EXTRACT(`{$column}`, '$.us') IS NULL OR JSON_LENGTH(JSON_EXTRACT(`{$column}`, '$.us')) = 0))",
                );
        }

        if ($driver === 'sqlite') {
            return $query
                ->whereNull($column)
                ->orWhereRaw(
                    "(json_type(\"{$column}\") = 'array' AND json_array_length(\"{$column}\") = 0)",
                )
                ->orWhereRaw(
                    "(json_type(\"{$column}\") = 'object' AND (json_type(\"{$column}\", '$.us') IS NULL OR json_array_length(json_extract(\"{$column}\", '$.us')) = 0))",
                );
        }

        return $query
            ->whereNull($column)
            ->orWhereJsonLength($column, 0);
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
