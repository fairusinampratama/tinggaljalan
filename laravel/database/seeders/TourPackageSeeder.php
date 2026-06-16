<?php

namespace Database\Seeders;

use App\Models\AddOn;
use App\Models\Destination;
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
                    'seo' => [
                        'source_refs' => $route['sourceRefs'] ?? [],
                        'image_credit' => $route['imageCredit'] ?? null,
                        'operator' => $route['operator'] ?? null,
                        'package_options' => $route['packageOptions'] ?? [],
                    ],
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

            $addOnIds = [];
            foreach (($route['addOns'] ?? []) as $addOnData) {
                $addOn = AddOn::updateOrCreate(
                    ['slug' => $addOnData['id']],
                    [
                        'title' => $this->localized($addOnData['title'] ?? $addOnData['id']),
                        'description' => $this->localized($addOnData['description'] ?? null),
                        'price_idr' => $addOnData['priceIdr'] ?? null,
                        'price_usd' => $addOnData['priceUsd'] ?? null,
                        'pricing_type' => $this->pricingType($addOnData['pricing'] ?? null),
                        'is_active' => true,
                    ],
                );

                $addOnIds[] = $addOn->id;
            }

            $package->addOns()->sync($addOnIds);
        }
    }
}
