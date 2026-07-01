<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\WhyChooseItem;
use App\Models\TrustStat;
use Database\Seeders\Concerns\LoadsPrototypeData;
use Illuminate\Database\Seeder;

class HomeContentSeeder extends Seeder
{
    use LoadsPrototypeData;

    public function run(): void
    {
        $data = $this->prototypeData();

        foreach ($data['homeTrustItems'] as $index => $item) {
            TrustStat::updateOrCreate(
                ['sort_order' => $index + 1],
                [
                    'title' => $this->localized($item['title']),
                    'value' => $this->localized($item['value']),
                    'icon_key' => $item['icon'] ?? null,
                    'is_active' => true,
                ],
            );
        }

        foreach ($data['homeReviews'] as $index => $review) {
            Review::updateOrCreate(
                [
                    'name' => $review['name'],
                    'sort_order' => $index + 1,
                ],
                [
                    'origin' => $this->localized($review['origin'] ?? null),
                    'rating' => $review['rating'] ?? 5,
                    'review_count' => $review['reviewCount'] ?? null,
                    'source' => $this->localized($review['source'] ?? null),
                    'text' => $this->localized($review['text']),
                    'is_featured' => true,
                    'is_active' => true,
                ],
            );
        }

        foreach (($data['whyChooseItems'] ?? []) as $index => $item) {
            WhyChooseItem::updateOrCreate(
                ['sort_order' => $index + 1],
                [
                    'title' => $item['title'] ?? null,
                    'text' => $item['text'] ?? null,
                    'icon' => $item['icon'] ?? 'compass',
                    'is_active' => true,
                ],
            );
        }
    }
}
