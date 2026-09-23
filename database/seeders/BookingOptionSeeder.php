<?php

namespace Database\Seeders;

use App\Models\Destination;
use App\Models\PackageAvailability;
use App\Models\TourPackage;
use App\Models\Voucher;
use Database\Seeders\Concerns\LoadsPrototypeData;
use Illuminate\Database\Seeder;

class BookingOptionSeeder extends Seeder
{
    use LoadsPrototypeData;

    public function run(): void
    {
        $bookingOptions = $this->prototypeData()['bookingOptions'];

        foreach (($bookingOptions['vouchers'] ?? []) as $code => $voucher) {
            $discountType = isset($voucher['percent']) ? 'percent' : 'fixed';
            $discountValue = $voucher['percent'] ?? $voucher['amount'] ?? 0;

            $voucherRecord = Voucher::updateOrCreate(
                ['code' => $code],
                [
                    'label' => $voucher['label'] ?? $code,
                    'public_title' => $voucher['publicTitle'] ?? null,
                    'public_description' => $voucher['publicDescription'] ?? null,
                    'discount_type' => $discountType,
                    'discount_value' => $discountValue,
                    'currency' => $voucher['currency'] ?? null,
                    'allowed_currencies' => $voucher['currencies'] ?? null,
                    'maximum_discount_idr' => $voucher['maximumDiscountIdr'] ?? null,
                    'maximum_discount_usd' => $voucher['maximumDiscountUsd'] ?? null,
                    'starts_at' => $voucher['startsAt'] ?? null,
                    'ends_at' => $voucher['endsAt'] ?? null,
                    'is_active' => true,
                    'is_public' => $voucher['isPublic'] ?? false,
                    'sort_order' => $voucher['sortOrder'] ?? 0,
                ],
            );

            $voucherRecord->tourPackages()->sync(
                TourPackage::query()
                    ->whereIn('slug', $voucher['tourPackages'] ?? [])
                    ->pluck('id')
                    ->all(),
            );
        }

        foreach (($bookingOptions['dateAvailabilityRules'] ?? []) as $rule) {
            $destination = Destination::query()->where('name', $rule['routeDestination'])->first();

            PackageAvailability::updateOrCreate(
                [
                    'destination_id' => $destination?->id,
                    'date' => $rule['date'],
                    'status' => $rule['status'],
                ],
                [
                    'end_date' => $rule['date'],
                    'seats_left' => $rule['seatsLeft'] ?? null,
                    'end_date' => $rule['date'],
                    'reason' => $rule['reason'] ?? null,
                ],
            );
        }

        foreach (($bookingOptions['blockedBookingRules'] ?? []) as $rule) {
            $destination = Destination::query()->where('name', $rule['routeDestination'])->first();

            PackageAvailability::updateOrCreate(
                [
                    'destination_id' => $destination?->id,
                    'date' => $rule['date'],
                    'status' => 'blocked',
                ],
                [
                    'reason' => $rule['reason'] ?? null,
                ],
            );
        }

    }
}
