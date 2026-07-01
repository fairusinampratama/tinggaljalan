<?php

namespace Tests\Unit;

use App\Filament\Support\BookingAttention;
use App\Models\Booking;
use App\Models\TourPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

class BookingAttentionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_booking_attention_statuses_match_operations_workflow(): void
    {
        Carbon::setTestNow('2026-06-24 10:00:00');
        $this->seed();

        $package = TourPackage::firstOrFail();

        $this->assertSame('Needs review', BookingAttention::status($this->booking([
            'booking_code' => 'TJ-ATTENTION-NEW',
            'tour_package_id' => $package->id,
            'destination_id' => $package->destination_id,
            'travel_date' => now()->addDays(14)->toDateString(),
            'status' => 'new',
        ])));

        $this->assertSame('Missing contact', BookingAttention::status($this->booking([
            'booking_code' => 'TJ-ATTENTION-CONTACT',
            'tour_package_id' => $package->id,
            'destination_id' => $package->destination_id,
            'whatsapp' => null,
            'email' => null,
            'travel_date' => now()->addDays(14)->toDateString(),
            'status' => 'confirmed',
        ])));

        $this->assertSame('Missing trip', BookingAttention::status($this->booking([
            'booking_code' => 'TJ-ATTENTION-TRIP',
            'tour_package_id' => null,
            'destination_id' => $package->destination_id,
            'travel_date' => null,
            'status' => 'confirmed',
        ])));

        $this->assertSame('Upcoming soon', BookingAttention::status($this->booking([
            'booking_code' => 'TJ-ATTENTION-SOON',
            'tour_package_id' => $package->id,
            'destination_id' => $package->destination_id,
            'travel_date' => now()->addDays(3)->toDateString(),
            'status' => 'confirmed',
        ])));

        $this->assertSame('Ready', BookingAttention::status($this->booking([
            'booking_code' => 'TJ-ATTENTION-READY',
            'tour_package_id' => $package->id,
            'destination_id' => $package->destination_id,
            'travel_date' => now()->addDays(14)->toDateString(),
            'status' => 'confirmed',
        ])));

        $this->assertSame('Closed', BookingAttention::status($this->booking([
            'booking_code' => 'TJ-ATTENTION-CLOSED',
            'tour_package_id' => $package->id,
            'destination_id' => $package->destination_id,
            'travel_date' => now()->addDays(14)->toDateString(),
            'status' => 'cancelled',
        ])));
    }

    public function test_transition_to_sets_matching_timestamp_without_mutating_snapshot(): void
    {
        Carbon::setTestNow('2026-06-24 10:00:00');
        $this->seed();

        $package = TourPackage::firstOrFail();
        $booking = $this->booking([
            'booking_code' => 'TJ-ATTENTION-TRANSITION',
            'tour_package_id' => $package->id,
            'destination_id' => $package->destination_id,
            'selected_add_ons' => [['title' => ['us' => 'Guide'], 'pricing_type' => 'per_booking']],
            'subtotal' => 700000,
            'discount_total' => 50000,
            'total' => 650000,
            'status' => 'new',
        ]);

        BookingAttention::transitionTo($booking, 'confirmed');

        $booking->refresh();
        $this->assertSame('confirmed', $booking->status);
        $this->assertTrue($booking->confirmed_at->equalTo(now()));
        $this->assertSame(700000, $booking->subtotal);
        $this->assertSame(50000, $booking->discount_total);
        $this->assertSame(650000, $booking->total);
        $this->assertSame('Guide', $booking->selected_add_ons[0]['title']['us']);

        $this->expectException(InvalidArgumentException::class);
        BookingAttention::transitionTo($booking, 'contacted');
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function booking(array $overrides = []): Booking
    {
        return Booking::create([
            'booking_code' => 'TJ-ATTENTION-'.str()->ulid(),
            'name' => 'Traveler Test',
            'email' => 'traveler@example.test',
            'whatsapp' => '+628111111111',
            'travel_date' => now()->addDays(14)->toDateString(),
            'pax' => 2,
            'traveler_type' => 'local',
            'currency' => 'IDR',
            'subtotal' => 700000,
            'discount_total' => 0,
            'total' => 700000,
            'status' => 'new',
            ...$overrides,
        ]);
    }
}
