<?php

namespace Database\Seeders;

use App\Models\Destination;
use App\Models\PackageAvailability;
use App\Models\Setting;
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

            Voucher::updateOrCreate(
                ['code' => $code],
                [
                    'label' => $voucher['label'] ?? $code,
                    'discount_type' => $discountType,
                    'discount_value' => $discountValue,
                    'currency' => $voucher['currency'] ?? null,
                    'allowed_currencies' => $voucher['currencies'] ?? null,
                    'is_active' => true,
                ],
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
                    'seats_left' => $rule['seatsLeft'] ?? null,
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

        foreach ([
            'destination_options',
            'pax_options',
            'pickup_options',
            'traveler_type_options',
            'payment_gateways',
            'currency_options',
            'initial_booking',
        ] as $key) {
            $sourceKey = str($key)->camel()->toString();

            Setting::updateOrCreate(
                ['group' => 'booking', 'key' => $key],
                ['value' => $bookingOptions[$sourceKey] ?? []],
            );
        }
    }
}
