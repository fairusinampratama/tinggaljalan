<?php

namespace App\Models;

use App\Models\Concerns\HasTravelScopes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

#[Fillable(['name', 'url', 'logo', 'alt', 'sort_order', 'is_active'])]
class PlatformLink extends Model
{
    use HasTravelScopes;

    public const MAX_ACTIVE = 4;

    protected static function booted(): void
    {
        static::saving(function (PlatformLink $platform): void {
            if ($platform->is_active && static::query()
                ->where('is_active', true)
                ->when($platform->exists, fn ($query) => $query->whereKeyNot($platform->getKey()))
                ->count() >= self::MAX_ACTIVE) {
                throw ValidationException::withMessages([
                    'is_active' => 'Only four platform links can be active. Deactivate another link first.',
                ]);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
