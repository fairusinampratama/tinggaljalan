<?php

namespace App\Filament\Support;

use App\Models\NewsArticle;
use Illuminate\Database\Eloquent\Builder;

class NewsArticleReadiness
{
    /**
     * @return array<int, string>
     */
    public static function missingItems(NewsArticle $article): array
    {
        return collect([
            $article->article_category_id ? null : 'Category',
            filled($article->title['us'] ?? null) ? null : 'English title',
            filled($article->slug) ? null : 'Slug',
            filled($article->cover_image) ? null : 'Cover image',
            filled($article->cover_alt['us'] ?? null) ? null : 'English cover alt text',
            filled($article->excerpt['us'] ?? null) ? null : 'English excerpt',
            self::hasCompleteEnglishSection($article->sections) ? null : 'Content sections',
        ])
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Evaluate the complete Filament form state before it is saved.
     *
     * @param  array<string, mixed>  $state
     * @return array<int, string>
     */
    public static function missingItemsFromState(array $state): array
    {
        return collect([
            filled($state['article_category_id'] ?? null) ? null : 'Category',
            filled($state['title']['us'] ?? null) ? null : 'English title',
            filled($state['slug'] ?? null) ? null : 'Slug',
            filled($state['cover_image'] ?? null) ? null : 'Cover image',
            filled($state['cover_alt']['us'] ?? null) ? null : 'English cover alt text',
            filled($state['excerpt']['us'] ?? null) ? null : 'English excerpt',
            self::hasCompleteEnglishSection($state['sections'] ?? []) ? null : 'Content sections',
        ])
            ->filter()
            ->values()
            ->all();
    }

    public static function status(NewsArticle $article): string
    {
        return self::isReady($article) ? 'Complete' : 'Incomplete';
    }

    public static function color(NewsArticle $article): string
    {
        return match (self::status($article)) {
            'Complete' => 'success',
            default => 'warning',
        };
    }

    public static function summary(NewsArticle $article): string
    {
        $missing = self::missingItems($article);

        if ($missing === []) {
            return "\u{2014}";
        }

        return implode(', ', $missing);
    }

    public static function isReady(NewsArticle $article): bool
    {
        return self::missingItems($article) === [];
    }

    public static function applyIncomplete(Builder $query): Builder
    {
        $incompleteIds = NewsArticle::query()
            ->get([
                'id',
                'article_category_id',
                'title',
                'slug',
                'cover_image',
                'cover_alt',
                'excerpt',
                'sections',
            ])
            ->reject(fn (NewsArticle $article): bool => self::isReady($article))
            ->modelKeys();

        return $query->whereKey($incompleteIds);
    }

    private static function hasCompleteEnglishSection(mixed $sections): bool
    {
        return collect($sections ?? [])->contains(
            fn ($section): bool => is_array($section)
                && filled($section['heading']['us'] ?? null)
                && filled($section['body']['us'] ?? null),
        );
    }
}
