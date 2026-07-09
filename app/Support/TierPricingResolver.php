<?php

namespace App\Support;

use App\Models\TourPackage;

class TierPricingResolver
{
    public function resolve(?TourPackage $package, int $pax, string $currency): array
    {
        $pax = max(1, $pax);
        if (! $package || $package->pricing_mode !== 'tiered') {
            $unit = $currency === 'USD' ? (int) $package?->base_price_usd : (int) $package?->base_price_idr;

            return $this->result('flat', 'priced', $pax, $currency, $unit, null, $unit);
        }

        $tiers = $package->priceTiers()->orderBy('min_pax')->get();
        $tier = $tiers->first(fn ($tier) => $pax >= $tier->min_pax && ($tier->max_pax === null || $pax <= $tier->max_pax));
        $first = $tiers->first();
        $reference = $currency === 'USD' ? (int) $first?->price_usd : (int) $first?->price_idr;

        if (! $tier) {
            return $this->result('tiered', 'quote_required', $pax, $currency, null, null, $reference);
        }
        $unit = $currency === 'USD' ? (int) $tier->price_usd : (int) $tier->price_idr;

        return $this->result('tiered', 'priced', $pax, $currency, $unit, $tier, $reference);
    }

    public function startingPrice(TourPackage $package, string $currency): int
    {
        if ($package->pricing_mode !== 'tiered') {
            return $currency === 'USD' ? (int) $package->base_price_usd : (int) $package->base_price_idr;
        }
        $tier = $package->priceTiers()->orderBy($currency === 'USD' ? 'price_usd' : 'price_idr')->first();

        return $currency === 'USD' ? (int) $tier?->price_usd : (int) $tier?->price_idr;
    }

    private function result(string $mode, string $status, int $pax, string $currency, ?int $unit, mixed $tier, int $reference): array
    {
        return [
            'mode' => $mode, 'status' => $status, 'currency' => $currency, 'pax' => $pax,
            'unit_price' => $unit, 'package_subtotal' => $unit === null ? null : $unit * $pax,
            'tier' => $tier ? ['id' => $tier->id, 'min_pax' => $tier->min_pax, 'max_pax' => $tier->max_pax] : null,
            'savings_per_person' => $unit === null ? null : max(0, $reference - $unit),
            'quote_required' => $status === 'quote_required',
        ];
    }
}
