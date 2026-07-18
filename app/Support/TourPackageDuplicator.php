<?php

namespace App\Support;

use App\Models\TourPackage;
use Illuminate\Support\Facades\DB;

class TourPackageDuplicator
{
    public function duplicate(TourPackage $package): TourPackage
    {
        return DB::transaction(function () use ($package): TourPackage {
            $source = TourPackage::query()->lockForUpdate()->findOrFail($package->getKey());
            $source->load(['itineraryItems', 'packageAddOns', 'priceTiers']);
            [$slug, $copyNumber] = $this->uniqueIdentity($source);

            $copy = $source->replicate();
            $copy->forceFill([
                'title' => $this->copiedTitle($source->title, $copyNumber),
                'slug' => $slug,
                'rating' => null,
                'review_count' => 0,
                'review_source' => null,
                'testimonials' => null,
                'sort_order' => ((int) TourPackage::query()->max('sort_order')) + 10,
                'is_featured' => false,
                'is_active' => false,
            ])->save();

            foreach ($source->itineraryItems as $item) {
                $copy->itineraryItems()->save($item->replicate(['tour_package_id']));
            }

            foreach ($source->packageAddOns as $addOn) {
                $copy->packageAddOns()->save($addOn->replicate(['tour_package_id']));
            }

            foreach ($source->priceTiers as $tier) {
                $copy->priceTiers()->save($tier->replicate(['tour_package_id']));
            }

            return $copy->load(['itineraryItems', 'packageAddOns', 'priceTiers']);
        });
    }

    /** @return array{string, int} */
    private function uniqueIdentity(TourPackage $source): array
    {
        $baseSlug = preg_replace('/-copy(?:-\d+)?$/', '', $source->slug) ?: $source->slug;
        $copyNumber = 1;

        do {
            $suffix = $copyNumber === 1 ? '-copy' : "-copy-{$copyNumber}";
            $slug = $baseSlug.$suffix;
            $copyNumber++;
        } while (TourPackage::query()->where('slug', $slug)->exists());

        return [$slug, $copyNumber - 1];
    }

    /**
     * @param  array<string, mixed>|null  $title
     * @return array<string, mixed>
     */
    private function copiedTitle(?array $title, int $copyNumber): array
    {
        $title ??= [];
        $baseTitle = preg_replace('/\s+– Copy(?: \d+)?$/u', '', (string) ($title['us'] ?? ''));
        $suffix = $copyNumber === 1 ? ' – Copy' : " – Copy {$copyNumber}";
        $title['us'] = trim((string) $baseTitle).$suffix;

        return $title;
    }
}
