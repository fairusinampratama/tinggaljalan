<?php

namespace App\Support;

use App\Models\TourPackage;
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
    public function evaluate(
        ?string $code,
        string $currency,
        ?TourPackage $package = null,
        bool $lockForUpdate = false,
        bool $checkUsage = true,
    ): array {
        $code = $this->normalizeCode($code);

        if ($code === '') {
            return ['state' => self::IDLE, 'voucher' => null];
        }

        $query = Voucher::query()->where('code', $code);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $voucher = $query->first();

        if (! $voucher || ! $this->isEligible($voucher, $currency, $package, $checkUsage)) {
            return ['state' => self::UNAVAILABLE, 'voucher' => null];
        }

        return ['state' => self::APPLIED, 'voucher' => $voucher];
    }

    public function existingBookingVoucher(?string $code, string $currency, ?TourPackage $package = null): ?Voucher
    {
        return $this->evaluate($code, $currency, $package, checkUsage: false)['voucher'];
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
        $promotionStatus = app(VoucherPromotionStatus::class);

        return Voucher::query()
            ->where('is_public', true)
            ->with(['tourPackages' => fn ($query) => $query->active()->ordered()])
            ->withCount([
                'bookings as active_redemptions_count' => fn ($query) => $query->where('status', '!=', 'cancelled'),
                'tourPackages as tour_packages_count',
                'tourPackages as active_tour_packages_count' => fn ($query) => $query->active(),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (Voucher $voucher): bool => $promotionStatus->isVisibleForCurrency(
                $promotionStatus->forVoucher($voucher),
                $currency,
            ))
            ->take($limit)
            ->values();
    }

    private function isEligible(Voucher $voucher, string $currency, ?TourPackage $package, bool $checkUsage): bool
    {
        if (! app(VoucherPromotionStatus::class)->isRedeemableForCurrency(
            $voucher,
            $currency,
            $checkUsage ? $this->redemptionCount($voucher) : 0,
            $checkUsage,
        )) {
            return false;
        }

        if ($package && ! $this->supportsPackage($voucher, $package)) {
            return false;
        }

        return true;
    }

    private function supportsPackage(Voucher $voucher, TourPackage $package): bool
    {
        $packages = $voucher->tourPackages();

        return ! $packages->exists() || $packages->whereKey($package->getKey())->exists();
    }
}
