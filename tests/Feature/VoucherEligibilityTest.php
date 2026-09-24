<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\PackageAvailability;
use App\Models\TourPackage;
use App\Models\Voucher;
use App\Support\PublicSite;
use App\Support\VoucherEligibilityService;
use App\Support\VoucherPromotionStatus;
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

    public function test_package_scoped_vouchers_only_apply_to_selected_packages(): void
    {
        $this->seed();
        $service = app(VoucherEligibilityService::class);
        $voucher = Voucher::query()->where('code', 'BROMO10')->firstOrFail();
        $allowedPackage = TourPackage::query()->where('slug', 'bromo-sunrise')->firstOrFail();
        $blockedPackage = TourPackage::query()->whereKeyNot($allowedPackage->id)->active()->firstOrFail();

        $voucher->update([
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => now()->addDay(),
            'discount_type' => 'percent',
            'discount_value' => 10,
            'allowed_currencies' => ['IDR', 'USD'],
        ]);
        $voucher->tourPackages()->sync([$allowedPackage->id]);

        $this->assertSame(VoucherEligibilityService::APPLIED, $service->evaluate($voucher->code, 'IDR', $allowedPackage)['state']);
        $this->assertSame(VoucherEligibilityService::UNAVAILABLE, $service->evaluate($voucher->code, 'IDR', $blockedPackage)['state']);

        $voucher->tourPackages()->detach();

        $this->assertSame(VoucherEligibilityService::APPLIED, $service->evaluate($voucher->code, 'IDR', $blockedPackage)['state']);
    }

    public function test_promotion_status_explains_visibility_outcomes_and_audiences(): void
    {
        $this->seed();
        $status = app(VoucherPromotionStatus::class);
        $voucher = Voucher::query()->where('code', 'BROMO10')->firstOrFail();

        $voucher->tourPackages()->detach();
        $voucher->update([
            'is_active' => true,
            'is_public' => true,
            'starts_at' => null,
            'ends_at' => now()->addDay(),
            'usage_limit' => null,
            'discount_type' => 'percent',
            'discount_value' => 10,
            'allowed_currencies' => ['IDR', 'USD'],
        ]);

        $live = $status->forVoucher($voucher->fresh());
        $this->assertSame(VoucherPromotionStatus::LIVE, $live['state']);
        $this->assertSame(['IDR', 'USD'], $live['currencies']);
        $this->assertCount(2, $live['audiences']);

        $voucher->update(['is_active' => false, 'is_public' => false, 'ends_at' => now()->subMinute()]);
        $inactive = $status->forVoucher($voucher->fresh());
        $this->assertSame(VoucherPromotionStatus::INACTIVE, $inactive['state']);
        $this->assertCount(3, $inactive['reasons']);

        $voucher->update(['is_active' => true, 'is_public' => true, 'starts_at' => now()->addHour(), 'ends_at' => now()->addDay()]);
        $this->assertSame(VoucherPromotionStatus::SCHEDULED, $status->forVoucher($voucher->fresh())['state']);

        $voucher->update(['starts_at' => null, 'ends_at' => now()->subMinute()]);
        $this->assertSame(VoucherPromotionStatus::EXPIRED, $status->forVoucher($voucher->fresh())['state']);

        $voucher->update(['ends_at' => now()->addDay(), 'usage_limit' => 1]);
        $this->bookingUsing($voucher, 'new');
        $this->assertSame(VoucherPromotionStatus::LIMIT_REACHED, $status->forVoucher($voucher->fresh())['state']);
    }

    public function test_scoped_promotion_without_active_packages_is_not_published(): void
    {
        $this->seed();
        $service = app(VoucherEligibilityService::class);
        $voucher = Voucher::query()->where('code', 'BROMO10')->firstOrFail();
        $package = TourPackage::query()->firstOrFail();

        Voucher::query()->where('id', '!=', $voucher->id)->update(['is_public' => false]);
        $voucher->update([
            'is_active' => true,
            'is_public' => true,
            'starts_at' => null,
            'ends_at' => now()->addDay(),
            'usage_limit' => null,
            'discount_type' => 'percent',
            'discount_value' => 10,
            'allowed_currencies' => ['IDR', 'USD'],
        ]);
        $voucher->tourPackages()->sync([$package->id]);
        $package->update(['is_active' => false]);

        $this->assertSame(VoucherPromotionStatus::NO_ACTIVE_PACKAGES, app(VoucherPromotionStatus::class)->forVoucher($voucher->fresh())['state']);
        $this->assertEmpty($service->publicPromotions('IDR'));
    }

    public function test_booking_summary_and_final_submission_reject_wrong_package_scope(): void
    {
        $this->seed();
        $voucher = Voucher::query()->where('code', 'BROMO10')->firstOrFail();
        $allowedPackage = TourPackage::query()->where('slug', 'bromo-sunrise')->firstOrFail();
        $blockedPackage = TourPackage::query()->whereKeyNot($allowedPackage->id)->active()->firstOrFail();
        $voucher->tourPackages()->sync([$allowedPackage->id]);
        $voucher->update([
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => now()->addYear(),
            'usage_limit' => null,
            'discount_type' => 'percent',
            'discount_value' => 10,
            'allowed_currencies' => ['IDR', 'USD'],
        ]);
        PackageAvailability::create([
            'tour_package_id' => $blockedPackage->id,
            'date' => '2040-01-15',
            'end_date' => '2040-01-15',
            'status' => 'available',
        ]);

        $draft = [
            'route' => $blockedPackage->slug,
            'date' => '2040-01-15',
            'pax' => 2,
            'pickup' => 'Malang Hotel',
            'traveler_type' => 'local',
            'add_ons' => [],
            'voucher' => $voucher->code,
            'voucher_applied' => true,
        ];

        $summary = PublicSite::bookingSummary($blockedPackage, $draft);
        $this->assertSame(VoucherEligibilityService::UNAVAILABLE, $summary['voucher_state']);
        $this->assertNull($summary['voucher']);

        $this->withoutMiddleware(PreventRequestForgery::class);
        $this->withSession(['booking_draft' => $draft])
            ->from('/checkout/review')
            ->post('/checkout/review', [
                'name' => 'Package Scope Test',
                'whatsapp_country' => 'ID',
                'whatsapp' => '08111111111',
                'email' => 'package-scope@example.test',
                'voucher' => $voucher->code,
            ])
            ->assertRedirect('/checkout/review')
            ->assertSessionHasErrors(['voucher']);

        $this->assertDatabaseMissing('bookings', ['email' => 'package-scope@example.test']);
    }

    public function test_public_promotions_respect_visibility_currency_schedule_usage_and_order(): void
    {
        $this->seed();
        $service = app(VoucherEligibilityService::class);
        $bromo = Voucher::query()->where('code', 'BROMO10')->firstOrFail();
        $local = Voucher::query()->where('code', 'TJHEMAT')->firstOrFail();
        Voucher::query()->whereNotIn('code', ['BROMO10', 'TJHEMAT'])->update(['is_public' => false]);

        $bromo->update([
            'is_public' => true,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'usage_limit' => 1,
            'sort_order' => 20,
        ]);
        $local->update([
            'is_public' => true,
            'starts_at' => null,
            'ends_at' => now()->addHour(),
            'sort_order' => 10,
        ]);

        $this->assertSame(['TJHEMAT', 'BROMO10'], $service->publicPromotions('IDR')->pluck('code')->all());
        $this->assertSame(['BROMO10'], $service->publicPromotions('USD')->pluck('code')->all());

        $booking = $this->bookingUsing($bromo, 'new');
        $this->assertSame(['TJHEMAT'], $service->publicPromotions('IDR')->pluck('code')->all());

        $booking->update(['status' => 'cancelled']);
        $bromo->update(['starts_at' => now()->addMinute()]);
        $this->assertSame(['TJHEMAT'], $service->publicPromotions('IDR')->pluck('code')->all());

        $bromo->update(['starts_at' => null, 'is_public' => false]);
        $this->assertSame(['TJHEMAT'], $service->publicPromotions('IDR')->pluck('code')->all());
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
