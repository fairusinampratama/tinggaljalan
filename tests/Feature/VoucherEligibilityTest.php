<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\PackageAvailability;
use App\Models\TourPackage;
use App\Models\Voucher;
use App\Support\VoucherEligibilityService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherEligibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_eligibility_covers_schedule_currency_and_normalized_codes(): void
    {
        $this->seed();
        $service = app(VoucherEligibilityService::class);
        $voucher = Voucher::query()->where('code', 'BROMO10')->firstOrFail();

        $voucher->update([
            'discount_type' => 'percent',
            'allowed_currencies' => ['IDR', 'USD'],
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'is_active' => true,
        ]);

        $this->assertSame(VoucherEligibilityService::APPLIED, $service->evaluate(' bromo10 ', 'IDR')['state']);
        $this->assertSame(VoucherEligibilityService::APPLIED, $service->evaluate('bromo10', 'USD')['state']);

        $voucher->update(['allowed_currencies' => []]);
        $this->assertSame(VoucherEligibilityService::UNAVAILABLE, $service->evaluate('BROMO10', 'IDR')['state']);
        $voucher->update(['allowed_currencies' => ['IDR', 'USD']]);

        $voucher->update(['is_active' => false]);
        $this->assertSame(VoucherEligibilityService::UNAVAILABLE, $service->evaluate('BROMO10', 'IDR')['state']);

        $voucher->update(['is_active' => true, 'starts_at' => now()->addMinute()]);
        $this->assertSame(VoucherEligibilityService::UNAVAILABLE, $service->evaluate('BROMO10', 'IDR')['state']);

        $voucher->update(['starts_at' => null, 'ends_at' => now()->subMinute()]);
        $this->assertSame(VoucherEligibilityService::UNAVAILABLE, $service->evaluate('BROMO10', 'IDR')['state']);

        $voucher->update([
            'discount_type' => 'fixed',
            'currency' => 'USD',
            'ends_at' => null,
        ]);
        $this->assertSame(VoucherEligibilityService::UNAVAILABLE, $service->evaluate('BROMO10', 'IDR')['state']);
        $this->assertSame(VoucherEligibilityService::APPLIED, $service->evaluate('BROMO10', 'USD')['state']);
        $this->assertSame(VoucherEligibilityService::IDLE, $service->evaluate(' ', 'USD')['state']);
    }

    public function test_usage_limit_counts_every_non_cancelled_booking_and_releases_cancellations(): void
    {
        $this->seed();
        $service = app(VoucherEligibilityService::class);
        $voucher = Voucher::query()->where('code', 'BROMO10')->firstOrFail();
        $voucher->update(['usage_limit' => 2]);

        $first = $this->bookingUsing($voucher, 'new');
        $this->bookingUsing($voucher, 'completed');

        $this->assertSame(2, $service->redemptionCount($voucher));
        $this->assertSame(VoucherEligibilityService::UNAVAILABLE, $service->evaluate($voucher->code, 'IDR')['state']);

        $first->update(['status' => 'cancelled']);

        $this->assertSame(1, $service->redemptionCount($voucher));
        $this->assertSame(VoucherEligibilityService::APPLIED, $service->evaluate($voucher->code, 'IDR')['state']);

        $voucher->update(['usage_limit' => null]);
        $this->assertSame(VoucherEligibilityService::APPLIED, $service->evaluate($voucher->code, 'IDR')['state']);
    }

    public function test_final_submission_rechecks_exhausted_voucher_without_creating_booking(): void
    {
        $this->seed();
        $this->withoutMiddleware(PreventRequestForgery::class);

        $package = TourPackage::query()->where('slug', 'bromo-sunrise')->firstOrFail();
        $voucher = Voucher::query()->where('code', 'BROMO10')->firstOrFail();
        $voucher->update(['usage_limit' => 1]);
        $this->bookingUsing($voucher, 'new', $package);
        PackageAvailability::create([
            'tour_package_id' => $package->id,
            'date' => '2030-08-01',
            'end_date' => '2030-08-01',
            'status' => 'available',
        ]);

        $draft = [
            'route' => $package->slug,
            'date' => '2030-08-01',
            'pax' => 2,
            'pickup' => 'Malang Hotel',
            'traveler_type' => 'local',
            'add_ons' => [],
            'voucher' => 'BROMO10',
            'voucher_applied' => true,
        ];

        $this->withSession(['booking_draft' => $draft])
            ->from('/checkout/review')
            ->post('/checkout/review', [
                'name' => 'Voucher Limit Test',
                'whatsapp_country' => 'ID',
                'whatsapp' => '08111111111',
                'email' => 'voucher-limit@example.test',
                'voucher' => 'BROMO10',
            ])
            ->assertRedirect('/checkout/review')
            ->assertSessionHasErrors(['voucher']);

        $this->assertDatabaseMissing('bookings', ['email' => 'voucher-limit@example.test']);
    }

    private function bookingUsing(Voucher $voucher, string $status, ?TourPackage $package = null): Booking
    {
        $package ??= TourPackage::query()->firstOrFail();

        return Booking::create([
            'booking_code' => 'TJ-VOUCHER-'.strtoupper(fake()->unique()->bothify('####??')),
            'tour_package_id' => $package->id,
            'destination_id' => $package->destination_id,
            'name' => 'Voucher Test',
            'email' => fake()->unique()->safeEmail(),
            'whatsapp' => '+628111111111',
            'travel_date' => now()->addMonth(),
            'pax' => 2,
            'traveler_type' => 'local',
            'currency' => 'IDR',
            'voucher_code' => $voucher->code,
            'subtotal' => 100000,
            'discount_total' => 10000,
            'total' => 90000,
            'status' => $status,
        ]);
    }
}
