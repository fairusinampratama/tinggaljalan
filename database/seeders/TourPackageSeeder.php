<?php

namespace Database\Seeders;

use App\Models\Destination;
use App\Models\PackageAddOn;
use App\Models\PackagePriceTier;
use App\Models\TourPackage;
use Database\Seeders\Concerns\LoadsPrototypeData;
use Illuminate\Database\Seeder;

class TourPackageSeeder extends Seeder
{
    use LoadsPrototypeData;

    public function run(): void
    {
        $destinations = Destination::query()->get()->keyBy('slug');

        foreach ($this->prototypeData()['routeArticles'] as $route) {
            $destination = $destinations[$route['destinationId']] ?? null;

            if (! $destination) {
                continue;
            }

            $package = TourPackage::updateOrCreate(
                ['slug' => $route['id']],
                [
                    'destination_id' => $destination->id,
                    'title' => $this->localized($route['title'] ?? $route['id']),
                    'category' => $this->localized($route['category'] ?? null),
                    'tag' => $this->localized($route['tag'] ?? $route['badge'] ?? null),
                    'excerpt' => $this->localized($route['why'] ?? $route['intro'] ?? null),
                    'intro' => $this->localized($route['intro'] ?? null),
                    'best_for' => $this->localized($route['bestFor'] ?? null),
                    'duration' => $route['duration'] ?? null,
                    'difficulty' => $this->localized($route['difficulty'] ?? null),
                    'pricing_mode' => $route['pricingMode'] ?? 'flat',
                    'base_price_idr' => $route['basePriceIdr'] ?? $route['basePrice'] ?? null,
                    'base_price_usd' => $route['basePriceUsd'] ?? null,
                    'price_note' => $route['priceNote'] ?? null,
                    'cover_image' => $route['image'] ?? null,
                    'cover_alt' => $this->localized($route['imageAlt'] ?? null),
                    'gallery' => $route['gallery'] ?? [],
                    'pickup_areas' => $route['pickupAreas'] ?? [],
                    'pickup_label' => $this->localized($route['pickupLabel'] ?? null),
                    'group_type' => $this->localized($route['groupType'] ?? null),
                    'highlights' => $route['highlights'] ?? [],
                    'includes' => $route['includes'] ?? [],
                    'excludes' => $route['excludes'] ?? [],
                    'notes' => $route['notes'] ?? [],
                    'details' => $route['details'] ?? [],
                    'good_to_know' => $route['goodToKnow'] ?? [],
                    'policies' => $route['policies'] ?? null,
                    'testimonials' => $route['testimonials'] ?? [],
                    'rating' => $route['rating'] ?? null,
                    'review_count' => $route['reviewCount'] ?? 0,
                    'review_source' => $this->localized($route['reviewSource'] ?? null),
                    'styles' => $route['styles'] ?? [],
                    'sort_order' => $route['sortOrder'] ?? 99,
                    'is_featured' => $route['featured'] ?? false,
                    'is_active' => true,
                ],
            );

            $package->itineraryItems()->delete();
            foreach (($route['itinerary'] ?? []) as $index => $item) {
                $package->itineraryItems()->create([
                    'day_number' => 1,
                    'title' => $this->localized($item),
                    'sort_order' => $index + 1,
                ]);
            }

            $tierKeys = [];
            foreach (($route['priceTiers'] ?? []) as $index => $tierData) {
                $tierKeys[] = $tierData['minPax'];
                PackagePriceTier::updateOrCreate(
                    [
                        'tour_package_id' => $package->id,
                        'min_pax' => $tierData['minPax'],
                    ],
                    [
                        'max_pax' => $tierData['maxPax'],
                        'price_idr' => $tierData['priceIdr'],
                        'price_usd' => $tierData['priceUsd'],
                        'sort_order' => $index,
                    ]
                );
            }
            if (!empty($tierKeys)) {
                $package->priceTiers()->whereNotIn('min_pax', $tierKeys)->delete();
            } else {
                $package->priceTiers()->delete();
            }

            $addOnKeys = [];
            foreach (($route['addOns'] ?? []) as $addOnData) {
                $sourceKey = $addOnData['id'];
                $addOnKeys[] = $sourceKey;

                PackageAddOn::updateOrCreate(
                    [
                        'tour_package_id' => $package->id,
                        'source_key' => $sourceKey,
                    ],
                    [
                        'title' => $this->localized($addOnData['title'] ?? $sourceKey),
                        'description' => $this->localized($addOnData['description'] ?? null),
                        'price_idr' => $addOnData['priceIdr'] ?? null,
                        'price_usd' => $addOnData['priceUsd'] ?? null,
                        'pricing_type' => $this->pricingType($addOnData['pricing'] ?? null),
                        'sort_order' => count($addOnKeys),
                        'is_active' => true,
                    ],
                );
            }

            if ($addOnKeys === []) {
                $package->packageAddOns()->delete();
            } else {
                $package->packageAddOns()
                    ->whereNotNull('source_key')
                    ->whereNotIn('source_key', $addOnKeys)
                    ->delete();
            }
        }
    }
}
