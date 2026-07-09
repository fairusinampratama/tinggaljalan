<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Destination;
use App\Models\PackagePriceTier;
use App\Models\TourPackage;
use App\Payments\BookingPaymentService;
use App\Support\BookingQuoteService;
use App\Support\TierPricingResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TieredPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_flat_pricing_remains_backward_compatible(): void
    {
        $price = app(TierPricingResolver::class)->resolve($this->package(['pricing_mode' => 'flat']), 3, 'IDR');

        $this->assertSame('priced', $price['status']);
        $this->assertSame(500000, $price['unit_price']);
        $this->assertSame(1500000, $price['package_subtotal']);
        $this->assertNull($price['tier']);
    }

    public function test_tier_boundaries_and_both_price_lists_are_resolved_per_person(): void
    {
        $package = $this->tieredPackage();

        $twoLocal = app(TierPricingResolver::class)->resolve($package, 2, 'IDR');
        $fourInternational = app(TierPricingResolver::class)->resolve($package, 4, 'USD');

        $this->assertSame(450000, $twoLocal['unit_price']);
        $this->assertSame(900000, $twoLocal['package_subtotal']);
        $this->assertSame(2, $twoLocal['tier']['min_pax']);
        $this->assertSame(3, $twoLocal['tier']['max_pax']);
        $this->assertSame(30, $fourInternational['unit_price']);
        $this->assertSame(120, $fourInternational['package_subtotal']);
        $this->assertSame(5, $fourInternational['savings_per_person']);
    }

    public function test_group_above_last_tier_requires_custom_quote(): void
    {
        $price = app(TierPricingResolver::class)->resolve($this->tieredPackage(), 6, 'IDR');

        $this->assertSame('quote_required', $price['status']);
        $this->assertTrue($price['quote_required']);
        $this->assertNull($price['unit_price']);
        $this->assertNull($price['package_subtotal']);
        $this->assertNull($price['tier']);
    }

    public function test_range_validation_rejects_gaps_overlaps_and_missing_prices(): void
    {
        foreach ([
            [['min_pax' => 2, 'max_pax' => 3, 'price_idr' => 1, 'price_usd' => 1]],
            [
                ['min_pax' => 1, 'max_pax' => 2, 'price_idr' => 1, 'price_usd' => 1],
                ['min_pax' => 2, 'max_pax' => 3, 'price_idr' => 1, 'price_usd' => 1],
            ],
            [['min_pax' => 1, 'max_pax' => 2, 'price_idr' => 1, 'price_usd' => null]],
        ] as $tiers) {
            $this->assertNotNull(PackagePriceTier::validateRanges($tiers));
        }
    }

    public function test_manual_quote_recalculates_snapshotted_add_ons_and_unblocks_payment(): void
    {
        $package = $this->tieredPackage();
        $booking = Booking::create([
            'booking_code' => 'TJ-QUOTE-001',
            'tour_package_id' => $package->id,
            'destination_id' => $package->destination_id,
            'name' => 'Large Group',
            'email' => 'group@example.test',
            'whatsapp' => '+628123456789',
            'travel_date' => now()->addMonth()->toDateString(),
            'pax' => 6,
            'traveler_type' => 'local',
            'currency' => 'IDR',
            'pricing_mode' => 'tiered',
            'pricing_status' => 'quote_required',
            'selected_add_ons' => [
                ['price_idr' => 10000, 'price_usd' => 1, 'pricing_type' => 'per_pax'],
                ['price_idr' => 50000, 'price_usd' => 4, 'pricing_type' => 'per_booking'],
            ],
            'subtotal' => 0,
            'discount_total' => 0,
            'total' => 0,
            'status' => 'confirmed',
        ]);

        try {
            app(BookingPaymentService::class)->createPaymentRequest($booking);
            $this->fail('Quote-required booking unexpectedly allowed payment.');
        } catch (\InvalidArgumentException) {
            $this->assertTrue(true);
        }

        $quoted = app(BookingQuoteService::class)->apply($booking, 400000);

        $this->assertSame('quoted', $quoted->pricing_status);
        $this->assertSame(400000, $quoted->unit_price);
        $this->assertSame(2400000, $quoted->package_subtotal);
        $this->assertSame(2510000, $quoted->subtotal);
        $this->assertSame(2510000, $quoted->total);
        $this->assertNotNull($quoted->quoted_at);
    }

    private function tieredPackage(): TourPackage
    {
        $package = $this->package(['pricing_mode' => 'tiered']);
        $package->priceTiers()->createMany([
            ['min_pax' => 1, 'max_pax' => 1, 'price_idr' => 500000, 'price_usd' => 35, 'sort_order' => 1],
            ['min_pax' => 2, 'max_pax' => 3, 'price_idr' => 450000, 'price_usd' => 32, 'sort_order' => 2],
            ['min_pax' => 4, 'max_pax' => 5, 'price_idr' => 400000, 'price_usd' => 30, 'sort_order' => 3],
        ]);

        return $package->refresh();
    }

    private function package(array $overrides = []): TourPackage
    {
        $destination = Destination::create(['slug' => 'tier-test', 'name' => 'Tier Test']);

        return TourPackage::create(array_merge([
            'destination_id' => $destination->id,
            'slug' => 'tier-test-package',
            'title' => ['en' => 'Tier Test Package'],
            'base_price_idr' => 500000,
            'base_price_usd' => 35,
            'is_active' => true,
        ], $overrides));
    }
}
