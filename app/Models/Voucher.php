<?php

namespace App\Models;

use App\Models\Concerns\HasTravelScopes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'label', 'discount_type', 'discount_value', 'currency', 'allowed_currencies', 'starts_at', 'ends_at', 'usage_limit', 'is_active'])]
class Voucher extends Model
{
    use HasTravelScopes;

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'allowed_currencies' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'usage_limit' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
