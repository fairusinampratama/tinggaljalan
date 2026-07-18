<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AboutPage extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'section_visibility' => 'array',
            'hero' => 'array',
            'story' => 'array',
            'values_section' => 'array',
            'team_section' => 'array',
            'milestones_section' => 'array',
            'workflow_section' => 'array',
            'profile_section' => 'array',
            'cta' => 'array',
            'seo' => 'array',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
