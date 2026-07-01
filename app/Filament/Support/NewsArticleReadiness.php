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
            filled($article->excerpt['us'] ?? null) ? null : 'English excerpt',
            self::hasEnglishList($article->sections) ? null : 'Content sections',
        ])
            ->filter()
            ->values()
            ->all();
    }

    public static function status(NewsArticle $article): string
    {
        if ($article->status !== 'published') {
            return 'Draft';
        }

        return self::isReady($article) ? 'Ready' : 'Needs work';
    }

    public static function color(NewsArticle $article): string
    {
        return match (self::status($article)) {
            'Ready' => 'success',
            'Draft' => 'gray',
            default => 'warning',
        };
    }

    public static function summary(NewsArticle $article): string
    {
        $missing = self::missingItems($article);

        if ($missing === []) {
            return $article->status === 'published' ? 'Ready' : 'Draft ready';
        }

        return implode(', ', $missing);
    }

    public static function isReady(NewsArticle $article): bool
    {
        return self::missingItems($article) === [];
    }

    public static function applyNeedsAttention(Builder $query): Builder
    {
        return $query
            ->where('status', 'draft')
            ->orWhereNull('article_category_id')
            ->orWhereNull('slug')
            ->orWhere('slug', '')
            ->orWhereNull('cover_image')
            ->orWhere('cover_image', '')
            ->orWhere(function (Builder $query) {
                $query->whereNull('title->us')->orWhere('title->us', '');
            })
            ->orWhere(function (Builder $query) {
                $query->whereNull('excerpt->us')->orWhere('excerpt->us', '');
            })
            ->orWhere(function (Builder $query) {
                $query->whereNull('sections')
                    ->orWhereJsonLength('sections', 0);
            });
    }

    private static function hasEnglishList(mixed $items): bool
    {
        if (is_array($items) && isset($items['us']) && is_array($items['us'])) {
            return collect($items['us'])->contains(fn ($item): bool => filled($item));
        }

        return collect($items ?? [])->contains(function ($item): bool {
            if (is_array($item)) {
                return filled($item['us'] ?? $item['id'] ?? $item['cn'] ?? $item['heading']['us'] ?? $item['body']['us'] ?? null);
            }

            return filled($item);
        });
    }
}
