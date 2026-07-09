<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tour_package_id', 'min_pax', 'max_pax', 'price_idr', 'price_usd', 'sort_order'])]
class PackagePriceTier extends Model
{
    protected function casts(): array
    {
        return ['min_pax' => 'integer', 'max_pax' => 'integer', 'price_idr' => 'integer', 'price_usd' => 'integer', 'sort_order' => 'integer'];
    }

    public function tourPackage(): BelongsTo
    {
        return $this->belongsTo(TourPackage::class);
    }

    public static function validateRanges(array $tiers): ?string
    {
        $tiers = collect($tiers)->sortBy(fn ($tier) => (int) ($tier['min_pax'] ?? 0))->values();
        if ($tiers->isEmpty()) {
            return 'Add at least one pricing tier.';
        }
        $expected = 1;
        foreach ($tiers as $index => $tier) {
            $min = (int) ($tier['min_pax'] ?? 0);
            if ($min !== $expected) {
                return "Ranges must be contiguous. The next range must start at {$expected}.";
            }
            if (empty($tier['price_idr']) || empty($tier['price_usd'])) {
                return 'Every tier requires both IDR and USD prices.';
            }

            if (! isset($tier['max_pax']) || $tier['max_pax'] === null || $tier['max_pax'] === '') {
                if ($index !== count($tiers) - 1) {
                    return 'Only the final tier can be open-ended (leave "To travelers" empty).';
                }
                break;
            }

            $max = (int) $tier['max_pax'];
            if ($max < $min) {
                return 'Each maximum must be equal to or greater than its minimum.';
            }
            $expected = $max + 1;
        }

        return null;
    }
}
