<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CompanyMilestone extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'period' => 'array',
            'title' => 'array',
            'description' => 'array',
            'image_alt' => 'array',
            'is_sample' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
