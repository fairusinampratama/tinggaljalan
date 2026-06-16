<?php

namespace App\Models;

use App\Models\Concerns\HasTravelScopes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'label', 'sort_order', 'is_active'])]
class ArticleCategory extends Model
{
    use HasTravelScopes;

    protected function casts(): array
    {
        return [
            'label' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function newsArticles(): HasMany
    {
        return $this->hasMany(NewsArticle::class);
    }
}
