<?php

namespace App\Models;

use App\Models\Concerns\HasTravelScopes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'value', 'icon_key', 'sort_order', 'is_active'])]
class TrustStat extends Model
{
    use HasTravelScopes;

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
