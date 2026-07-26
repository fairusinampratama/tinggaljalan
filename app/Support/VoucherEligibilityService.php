<?php

namespace App\Support;

use App\Models\Voucher;

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

    private function isEligible(Voucher $voucher, string $currency, bool $checkUsage): bool
    {
        if (! $voucher->is_active
            || ($voucher->starts_at && $voucher->starts_at->isFuture())
            || ($voucher->ends_at && $voucher->ends_at->isPast())) {
            return false;
        }

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

        return ! $checkUsage
            || $voucher->usage_limit === null
            || $this->redemptionCount($voucher) < $voucher->usage_limit;
    }
}
