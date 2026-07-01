<?php

namespace App\Support;

use App\Models\PackageAvailability;
use App\Models\TourPackage;
use Illuminate\Support\Collection;

class PackageAvailabilityResolver
{
    public function resolve(?TourPackage $package, ?string $date): ?PackageAvailability
    {
        if (! $package || ! $date) {
            return null;
        }

        return PackageAvailability::query()
            ->whereDate('date', $date)
            ->where('tour_package_id', $package->id)
            ->first()
            ?? PackageAvailability::query()
                ->whereDate('date', $date)
                ->whereNull('tour_package_id')
                ->where('destination_id', $package->destination_id)
                ->first();
    }

    /**
     * @return Collection<string, PackageAvailability>
     */
    public function rulesByDate(TourPackage $package): Collection
    {
        $destinationRules = PackageAvailability::query()
            ->whereNull('tour_package_id')
            ->where('destination_id', $package->destination_id)
            ->get()
            ->keyBy(fn (PackageAvailability $rule): string => $rule->date->toDateString());

        $packageRules = PackageAvailability::query()
            ->where('tour_package_id', $package->id)
            ->get()
            ->keyBy(fn (PackageAvailability $rule): string => $rule->date->toDateString());

        return $destinationRules->replace($packageRules);
    }
}