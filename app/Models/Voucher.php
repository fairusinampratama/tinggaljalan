<?php

namespace App\Models;

use App\Models\Concerns\HasTravelScopes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'label', 'discount_type', 'discount_value', 'currency', 'allowed_currencies', 'starts_at', 'ends_at', 'usage_limit', 'is_active'])]
class Voucher extends Model
{
    use HasTravelScopes;

    protected static function booted(): void
    {
        static::saving(function (Voucher $voucher): void {
            $voucher->code = strtoupper(trim($voucher->code));
            $voucher->allowed_currencies = collect($voucher->allowed_currencies ?? [])
                ->map(fn ($currency): string => strtoupper(trim((string) $currency)))
                ->filter(fn (string $currency): bool => in_array($currency, ['IDR', 'USD'], true))
                ->unique()
                ->values()
                ->all();

            if ($voucher->discount_type === 'percent') {
                $voucher->currency = null;
            } elseif ($voucher->discount_type === 'fixed' && filled($voucher->currency)) {
                $voucher->currency = strtoupper(trim($voucher->currency));
                $voucher->allowed_currencies = [$voucher->currency];
            }
        });
    }

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

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'voucher_code', 'code');
    }
}
