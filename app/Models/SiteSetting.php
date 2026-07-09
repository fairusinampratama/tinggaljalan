<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'service_hours' => 'array',
            'service_areas' => 'array',
            'trust_badges' => 'array',
            'hero_autoplay_enabled' => 'boolean',
            'hero_autoplay_interval' => 'integer',
        ];
    }
}
