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
                    'tags' => $this->localizedTags($articleData['tags'] ?? []),
                    'sections' => $this->localizedSections($articleData['sections'] ?? []),
                    'reading_time' => $this->localized($articleData['readingTime'] ?? null),
                    'published_at' => $articleData['publishedDate'] ?? null,
                    'content_updated_at' => $articleData['updatedDate'] ?? null,
                    'status' => 'published',
                    'is_featured' => $index === 0,
                    'seo' => $this->localizedSeo($articleData['seo'] ?? null),
                ],
            );

            $relatedPackageIds = collect($articleData['relatedRouteIds'] ?? [])
                ->map(fn (string $slug) => $packages[$slug]?->id ?? null)
                ->filter()
                ->all();

            $article->tourPackages()->sync($relatedPackageIds);
        }
    }

    private function localizedTags(mixed $tags): array
    {
        if (! is_array($tags)) {
            return [];
        }

        return collect($tags)
            ->map(fn ($tag) => $this->localized($tag))
            ->filter(fn ($tag): bool => filled($tag['us'] ?? null) || filled($tag['id'] ?? null) || filled($tag['cn'] ?? null))
            ->values()
            ->all();
    }

    private function localizedSections(mixed $sections): array
    {
        if (! is_array($sections)) {
            return [];
        }

        return collect($sections)
            ->map(function ($section, int $index): ?array {
                if (! is_array($section)) {
                    return null;
                }

                $heading = $this->localized($section['heading'] ?? $section['title'] ?? ['us' => 'Section '.($index + 1)]);
                $body = $this->localized($section['body'] ?? $section['content'] ?? $section['text'] ?? null);

                if (! $body || (! filled($body['us'] ?? null) && ! filled($body['id'] ?? null) && ! filled($body['cn'] ?? null))) {
                    return null;
                }

                return [
                    'heading' => $heading,
                    'body' => $body,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function localizedSeo(mixed $seo): ?array
    {
        if (! is_array($seo)) {
            return null;
        }

        return [
            'title' => $this->localized($seo['title'] ?? null),
            'description' => $this->localized($seo['description'] ?? null),
        ];
    }
}
