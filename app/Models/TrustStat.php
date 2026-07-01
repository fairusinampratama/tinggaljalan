<?php

namespace App\Models;

use App\Models\Concerns\HasTravelScopes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

#[Fillable(['title', 'value', 'icon_key', 'sort_order', 'is_active'])]
class TrustStat extends Model
{
    use HasTravelScopes;

    public const MAX_ACTIVE = 4;

    public const ICONS = ['compass', 'map-pin', 'shield-check', 'star'];

    protected static function booted(): void
    {
        static::saving(function (TrustStat $stat): void {
            if (! in_array($stat->icon_key, self::ICONS, true)) {
                throw ValidationException::withMessages(['icon_key' => 'Choose one of the supported trust stat icons.']);
            }

            if ($stat->is_active && static::query()
                ->where('is_active', true)
                ->when($stat->exists, fn ($query) => $query->whereKeyNot($stat->getKey()))
                ->count() >= self::MAX_ACTIVE) {
                throw ValidationException::withMessages([
                    'is_active' => 'Only four trust stats can be active. Deactivate another stat first.',
                ]);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'value' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
