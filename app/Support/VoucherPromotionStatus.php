<?php

namespace App\Support;

use App\Models\TourPackage;
use App\Models\Voucher;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class VoucherPromotionStatus
{
    public const LIVE = 'live';

    public const SCHEDULED = 'scheduled';

    public const EXPIRED = 'expired';

    public const INACTIVE = 'inactive';

    public const HOMEPAGE_DISABLED = 'homepage_disabled';

    public const LIMIT_REACHED = 'limit_reached';

    public const NO_ACTIVE_PACKAGES = 'no_active_packages';

    public const INVALID = 'invalid';

    /**
     * @return array{
     *     state: string,
     *     label: string,
     *     color: string,
     *     reason: string,
     *     reasons: array<int, string>,
     *     is_visible: bool,
     *     currencies: array<int, string>,
     *     audiences: array<int, string>,
     *     redemption_count: int,
     *     usage_limit: int|null,
     *     starts_at: CarbonInterface|null,
     *     ends_at: CarbonInterface|null,
     *     scoped_package_count: int,
     *     active_package_count: int,
     *     applies_to_all_packages: bool,
     *     sort_order: int
     * }
     */
    public function forVoucher(Voucher $voucher): array
    {
        $redemptionCount = $this->countAttributeOrQuery(
            $voucher,
            'active_redemptions_count',
            fn (): int => $voucher->bookings()->where('status', '!=', 'cancelled')->count(),
        );
        $scopedPackageCount = $this->countAttributeOrQuery(
            $voucher,
            'tour_packages_count',
            fn (): int => $voucher->tourPackages()->count(),
        );
        $activePackageCount = $this->countAttributeOrQuery(
            $voucher,
            'active_tour_packages_count',
            fn (): int => $voucher->tourPackages()->active()->count(),
        );

        return $this->evaluate([
            'discount_type' => $voucher->discount_type,
            'discount_value' => $voucher->discount_value,
            'currency' => $voucher->currency,
            'allowed_currencies' => $voucher->allowed_currencies,
            'starts_at' => $voucher->starts_at,
            'ends_at' => $voucher->ends_at,
            'usage_limit' => $voucher->usage_limit,
            'is_active' => $voucher->is_active,
            'is_public' => $voucher->is_public,
            'sort_order' => $voucher->sort_order,
        ], $redemptionCount, $scopedPackageCount, $activePackageCount);
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    public function forFormState(array $state, ?Voucher $record = null): array
    {
        $packageIds = collect($state['tourPackages'] ?? [])
            ->filter()
            ->unique()
            ->values();
        $redemptionCount = $record
            ? $record->bookings()->where('status', '!=', 'cancelled')->count()
            : 0;
        $activePackageCount = $packageIds->isEmpty()
            ? 0
            : TourPackage::query()->active()->whereKey($packageIds)->count();

        return $this->evaluate(
            $state,
            $redemptionCount,
            $packageIds->count(),
            $activePackageCount,
        );
    }

    /** @param array<string, mixed> $status */
    public function isVisibleForCurrency(array $status, string $currency): bool
    {
        return $status['is_visible']
            && in_array(strtoupper($currency), $status['currencies'], true);
    }

    public function isRedeemableForCurrency(
        Voucher $voucher,
        string $currency,
        int $redemptionCount,
        bool $checkUsage = true,
    ): bool {
        if (! $voucher->is_active
            || $voucher->starts_at?->isFuture()
            || $voucher->ends_at?->isPast()) {
            return false;
        }

        if (! in_array(strtoupper($currency), $this->supportedCurrencies([
            'discount_type' => $voucher->discount_type,
            'discount_value' => $voucher->discount_value,
            'currency' => $voucher->currency,
            'allowed_currencies' => $voucher->allowed_currencies,
        ]), true)) {
            return false;
        }

        return ! $checkUsage
            || $voucher->usage_limit === null
            || $redemptionCount < $voucher->usage_limit;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function evaluate(array $state, int $redemptionCount, int $scopedPackageCount, int $activePackageCount): array
    {
        $startsAt = $this->date($state['starts_at'] ?? null);
        $endsAt = $this->date($state['ends_at'] ?? null);
        $usageLimit = filled($state['usage_limit'] ?? null) ? (int) $state['usage_limit'] : null;
        $currencies = $this->supportedCurrencies($state);
        $blockers = [];

        if (! (bool) ($state['is_active'] ?? false)) {
            $blockers[] = [self::INACTIVE, 'Inactive', 'danger', 'The voucher is disabled globally.'];
        }

        if (! (bool) ($state['is_public'] ?? false)) {
            $blockers[] = [self::HOMEPAGE_DISABLED, 'Homepage disabled', 'gray', 'Show on homepage is turned off.'];
        }

        if ($startsAt?->isFuture()) {
            $blockers[] = [self::SCHEDULED, 'Scheduled', 'warning', 'Starts '.$startsAt->format('M j, Y H:i').'.'];
        }

        if ($endsAt?->isPast()) {
            $blockers[] = [self::EXPIRED, 'Expired', 'danger', 'Ended '.$endsAt->format('M j, Y H:i').'.'];
        }

        if ($usageLimit !== null && $redemptionCount >= $usageLimit) {
            $blockers[] = [self::LIMIT_REACHED, 'Limit reached', 'danger', "All {$usageLimit} uses have been redeemed."];
        }

        if ($scopedPackageCount > 0 && $activePackageCount === 0) {
            $blockers[] = [self::NO_ACTIVE_PACKAGES, 'No active trips', 'danger', 'None of the selected trips are currently active.'];
        }

        if ($currencies === []) {
            $blockers[] = [self::INVALID, 'Invalid configuration', 'danger', 'The discount value or eligible currency configuration is invalid.'];
        }

        [$stateName, $label, $color, $reason] = $blockers[0]
            ?? [self::LIVE, 'Live', 'success', 'Visible on the homepage now.'];

        return [
            'state' => $stateName,
            'label' => $label,
            'color' => $color,
            'reason' => $reason,
            'reasons' => array_values(array_unique(array_column($blockers, 3))),
            'is_visible' => $blockers === [],
            'currencies' => $currencies,
            'audiences' => $this->audiences($currencies),
            'redemption_count' => $redemptionCount,
            'usage_limit' => $usageLimit,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'scoped_package_count' => $scopedPackageCount,
            'active_package_count' => $activePackageCount,
            'applies_to_all_packages' => $scopedPackageCount === 0,
            'sort_order' => max(0, (int) ($state['sort_order'] ?? 0)),
        ];
    }

    /** @param array<string, mixed> $state */
    private function supportedCurrencies(array $state): array
    {
        $value = (float) ($state['discount_value'] ?? 0);

        if (($state['discount_type'] ?? null) === 'fixed') {
            $currency = strtoupper(trim((string) ($state['currency'] ?? '')));

            return $value > 0 && in_array($currency, ['IDR', 'USD'], true) ? [$currency] : [];
        }

        if (($state['discount_type'] ?? null) !== 'percent' || $value <= 0 || $value > 100) {
            return [];
        }

        return collect($state['allowed_currencies'] ?? [])
            ->map(fn ($currency): string => strtoupper(trim((string) $currency)))
            ->filter(fn (string $currency): bool => in_array($currency, ['IDR', 'USD'], true))
            ->unique()
            ->values()
            ->all();
    }

    /** @param array<int, string> $currencies */
    private function audiences(array $currencies): array
    {
        $audiences = [];

        if (in_array('IDR', $currencies, true)) {
            $audiences[] = 'Indonesian visitors (IDR)';
        }

        if (in_array('USD', $currencies, true)) {
            $audiences[] = 'English and Chinese visitors (USD)';
        }

        return $audiences;
    }

    private function date(mixed $value): ?CarbonInterface
    {
        if (blank($value)) {
            return null;
        }

        return $value instanceof CarbonInterface ? $value : Carbon::parse($value);
    }

    private function countAttributeOrQuery(Voucher $voucher, string $attribute, callable $query): int
    {
        return array_key_exists($attribute, $voucher->getAttributes())
            ? (int) $voucher->getAttribute($attribute)
            : $query();
    }
}
