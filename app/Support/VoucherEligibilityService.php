<?php

namespace App\Support;

use App\Models\Voucher;
use Illuminate\Support\Collection;

class VoucherEligibilityService
{
    public const APPLIED = 'applied';

    public const IDLE = 'idle';

    public const UNAVAILABLE = 'unavailable';

    /**
     * @return array{state: string, voucher: Voucher|null}
     */
    public function evaluate(?string $code, string $currency, bool $lockForUpdate = false, bool $checkUsage = true): array
    {
        $code = $this->normalizeCode($code);

        if ($code === '') {
            return ['state' => self::IDLE, 'voucher' => null];
        }

        $query = Voucher::query()->where('code', $code);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $voucher = $query->first();

        if (! $voucher || ! $this->isEligible($voucher, $currency, $checkUsage)) {
            return ['state' => self::UNAVAILABLE, 'voucher' => null];
        }

        return ['state' => self::APPLIED, 'voucher' => $voucher];
    }

    public function existingBookingVoucher(?string $code, string $currency): ?Voucher
    {
        return $this->evaluate($code, $currency, checkUsage: false)['voucher'];
    }

    public function normalizeCode(?string $code): string
    {
        return strtoupper(trim((string) $code));
    }

    public function redemptionCount(Voucher $voucher): int
    {
        return $voucher->bookings()->where('status', '!=', 'cancelled')->count();
    }

    /**
     * @return Collection<int, Voucher>
     */
    public function publicPromotions(string $currency, int $limit = 8): Collection
    {
        return Voucher::query()
            ->where('is_active', true)
            ->where('is_public', true)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->with(['tourPackages' => fn ($query) => $query->active()->ordered()])
            ->withCount([
                'bookings as active_redemptions_count' => fn ($query) => $query->where('status', '!=', 'cancelled'),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (Voucher $voucher): bool => $this->supportsCurrency($voucher, $currency)
                && $this->hasUsageRemaining($voucher, (int) $voucher->active_redemptions_count))
            ->take($limit)
            ->values();
    }

    private function isEligible(Voucher $voucher, string $currency, bool $checkUsage): bool
    {
        if (! $voucher->is_active
            || ($voucher->starts_at && $voucher->starts_at->isFuture())
            || ($voucher->ends_at && $voucher->ends_at->isPast())) {
            return false;
        }

        if (! $this->supportsCurrency($voucher, $currency)) {
            return false;
        }

        return ! $checkUsage || $this->hasUsageRemaining($voucher, $this->redemptionCount($voucher));
    }

    private function supportsCurrency(Voucher $voucher, string $currency): bool
    {
        $currency = strtoupper($currency);

        $allowedCurrencies = $voucher->allowed_currencies ?? [];
        $discountValue = (float) $voucher->discount_value;

        if ($voucher->discount_type === 'fixed') {
            if ($discountValue <= 0 || ! $voucher->currency || $voucher->currency !== $currency) {
                return false;
            }
        } elseif ($voucher->discount_type === 'percent') {
            if ($discountValue <= 0 || $discountValue > 100 || $allowedCurrencies === [] || ! in_array($currency, $allowedCurrencies, true)) {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

    private function hasUsageRemaining(Voucher $voucher, int $redemptionCount): bool
    {
        return $voucher->usage_limit === null || $redemptionCount < $voucher->usage_limit;
    }
}
