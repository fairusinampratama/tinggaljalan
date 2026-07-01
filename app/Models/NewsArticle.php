<?php

namespace App\Models;

use App\Models\Concerns\HasTravelScopes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['destination_id', 'article_category_id', 'slug', 'title', 'excerpt', 'cover_image', 'cover_alt', 'tags', 'sections', 'reading_time', 'published_at', 'content_updated_at', 'status', 'is_featured', 'seo'])]
class NewsArticle extends Model
{
    use HasTravelScopes;

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'excerpt' => 'array',
            'cover_alt' => 'array',
            'tags' => 'array',
            'sections' => 'array',
            'reading_time' => 'array',
            'published_at' => 'datetime',
            'content_updated_at' => 'datetime',
            'is_featured' => 'boolean',
            'seo' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (NewsArticle $article) {
            if ($article->isDirty('status') && $article->status === 'published' && is_null($article->published_at)) {
                $article->published_at = now();
            }
            if ($article->isDirty(['title', 'excerpt', 'sections']) && $article->exists) {
                $article->content_updated_at = now();
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function articleCategory(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class);
    }

    public function tourPackages(): BelongsToMany
    {
        return $this->belongsToMany(TourPackage::class);
    }
}
