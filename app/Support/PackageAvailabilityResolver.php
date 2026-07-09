<?php

namespace App\Support;

use App\Models\PackageAvailability;
use App\Models\TourPackage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PackageAvailabilityResolver
{
    public function resolve(?TourPackage $package, ?string $date): ?PackageAvailability
    {
        if (! $package || ! $date) {
            return null;
        }

        return $this->matchingDate(PackageAvailability::query(), $date)
            ->where('tour_package_id', $package->id)
            ->latest('date')
            ->first()
            ?? $this->matchingDate(PackageAvailability::query(), $date)
                ->whereNull('tour_package_id')
                ->where('destination_id', $package->destination_id)
                ->latest('date')
                ->first();
    }

    /**
     * Package rules are returned first because they override destination rules.
     *
     * @return Collection<int, PackageAvailability>
     */
    public function rules(TourPackage $package): Collection
    {
        $packageRules = PackageAvailability::query()
            ->where('tour_package_id', $package->id)
            ->orderBy('date')
            ->get();

        $destinationRules = PackageAvailability::query()
            ->whereNull('tour_package_id')
            ->where('destination_id', $package->destination_id)
            ->orderBy('date')
            ->get();

        return $packageRules->concat($destinationRules)->values();
    }

    /**
     * Retained for compatibility with single-day consumers.
     *
     * @return Collection<string, PackageAvailability>
     */
    public function rulesByDate(TourPackage $package): Collection
    {
        $destinationRules = PackageAvailability::query()
            ->whereNull('tour_package_id')
            ->where('destination_id', $package->destination_id)
            ->where('is_open_ended', false)
            ->whereColumn('date', 'end_date')
            ->get()
            ->keyBy(fn (PackageAvailability $rule): string => $rule->date->toDateString());

        $packageRules = PackageAvailability::query()
            ->where('tour_package_id', $package->id)
            ->where('is_open_ended', false)
            ->whereColumn('date', 'end_date')
            ->get()
            ->keyBy(fn (PackageAvailability $rule): string => $rule->date->toDateString());

        return $destinationRules->replace($packageRules);
    }

    private function matchingDate(Builder $query, string $date): Builder
    {
        return $query
            ->whereDate('date', '<=', $date)
            ->where(function (Builder $query) use ($date): void {
                $query
                    ->where('is_open_ended', true)
                    ->orWhereNull('end_date')
                    ->orWhereDate('end_date', '>=', $date);
            });
    }
}
