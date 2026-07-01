<?php

namespace App\Models;

use App\Models\Concerns\HasTravelScopes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['slug', 'label', 'description', 'sort_order', 'is_active'])]
class RouteFilter extends Model
{
    use HasTravelScopes;

    protected function casts(): array
    {
        return [
            'label' => 'array',
            'description' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
