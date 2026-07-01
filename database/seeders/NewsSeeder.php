<?php

namespace Database\Seeders;

use App\Models\ArticleCategory;
use App\Models\Destination;
use App\Models\NewsArticle;
use App\Models\TourPackage;
use Database\Seeders\Concerns\LoadsPrototypeData;
use Illuminate\Database\Seeder;

class NewsSeeder extends Seeder
{
    use LoadsPrototypeData;

    public function run(): void
    {
        $data = $this->prototypeData();

        foreach ($data['newsCategories'] as $index => $category) {
            ArticleCategory::updateOrCreate(
                ['slug' => $category['value']],
                [
                    'label' => $this->localized($category['label']),
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ],
            );
        }

        $categories = ArticleCategory::query()->get()->keyBy('slug');
        $destinations = Destination::query()->get()->keyBy('slug');
        $packages = TourPackage::query()->get()->keyBy('slug');

        foreach ($data['newsArticles'] as $index => $articleData) {
            $category = $categories[$articleData['category']] ?? null;

            if (! $category) {
                continue;
            }

            $article = NewsArticle::updateOrCreate(
                ['slug' => $articleData['slug']],
                [
                    'destination_id' => $destinations[$articleData['destinationId']]?->id ?? null,
                    'article_category_id' => $category->id,
                    'title' => $this->localized($articleData['title']),
                    'excerpt' => $this->localized($articleData['excerpt']),
                    'cover_image' => $articleData['coverImage'] ?? null,
                    'cover_alt' => $this->localized($articleData['coverAlt'] ?? null),
                    'tags' => $articleData['tags'] ?? [],
                    'sections' => $articleData['sections'] ?? [],
                    'reading_time' => $this->localized($articleData['readingTime'] ?? null),
                    'published_at' => $articleData['publishedDate'] ?? null,
                    'content_updated_at' => $articleData['updatedDate'] ?? null,
                    'status' => 'published',
                    'is_featured' => $index === 0,
                    'seo' => $articleData['seo'] ?? null,
                ],
            );

            $relatedPackageIds = collect($articleData['relatedRouteIds'] ?? [])
                ->map(fn (string $slug) => $packages[$slug]?->id ?? null)
                ->filter()
                ->all();

            $article->tourPackages()->sync($relatedPackageIds);
        }
    }
}
