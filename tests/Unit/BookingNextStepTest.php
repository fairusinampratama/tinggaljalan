<?php

namespace Tests\Unit;

use App\Filament\Support\BookingNextStep;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\TourPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookingNextStepTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_booking_next_step_guides_admin_through_operations_workflow(): void
    {
        Carbon::setTestNow('2026-06-26 10:00:00');
        $this->seed();

        $package = TourPackage::firstOrFail();

        $this->assertNextStep('Confirm availability', $this->booking([
            'booking_code' => 'TJ-NEXT-NEW',
            'tour_package_id' => $package->id,
            'destination_id' => $package->destination_id,
            'status' => 'new',
        ]));

        $this->assertNextStep('Add contact details', $this->booking([
            'booking_code' => 'TJ-NEXT-CONTACT',
            'tour_package_id' => $package->id,
            'destination_id' => $package->destination_id,
            'email' => null,
            'whatsapp' => null,
            'status' => 'new',
        ]));

        $this->assertNextStep('Complete trip details', $this->booking([
            'booking_code' => 'TJ-NEXT-TRIP',
            'tour_package_id' => null,
            'travel_date' => null,
            'status' => 'new',
        ]));


        $confirmed = $this->booking([
            'booking_code' => 'TJ-NEXT-PAYMENT',
            'tour_package_id' => $package->id,
            'destination_id' => $package->destination_id,
            'status' => 'confirmed',
        ]);
        $this->assertNextStep('Create payment request', $confirmed);

        $this->payment($confirmed, ['status' => 'pending']);
        $this->assertNextStep('Send invoice or WhatsApp', $confirmed->refresh());

        $confirmed->latestPayment->update(['sent_at' => now(), 'status' => 'invoice_sent']);
        $this->assertNextStep('Wait for payment / sync', $confirmed->refresh());

        $confirmed->latestPayment->update(['status' => 'paid', 'paid_at' => now()]);
        $this->assertNextStep('Prepare trip', $confirmed->refresh());

        $pastPaid = $this->booking([
            'booking_code' => 'TJ-NEXT-COMPLETE',
            'tour_package_id' => $package->id,
            'destination_id' => $package->destination_id,
            'status' => 'confirmed',
            'travel_date' => now()->subDay()->toDateString(),
        ]);
        $this->payment($pastPaid, ['status' => 'paid', 'paid_at' => now()]);
        $this->assertNextStep('Mark trip completed', $pastPaid->refresh());

        foreach (['expired' => 'Create new payment or cancel', 'failed' => 'Review payment request', 'cancelled' => 'Review payment request'] as $status => $label) {
            $booking = $this->booking([
                'booking_code' => 'TJ-NEXT-PAYMENT-'.strtoupper($status),
                'tour_package_id' => $package->id,
                'destination_id' => $package->destination_id,
                'status' => 'confirmed',
            ]);
            $this->payment($booking, ['status' => $status]);
            $this->assertNextStep($label, $booking->refresh());
        }

        $this->assertNextStep('Closed', $this->booking([
            'booking_code' => 'TJ-NEXT-CANCELLED',
            'tour_package_id' => $package->id,
            'destination_id' => $package->destination_id,
            'status' => 'cancelled',
        ]));

        $this->assertNextStep('Closed', $this->booking([
            'booking_code' => 'TJ-NEXT-CLOSED',
            'tour_package_id' => $package->id,
            'destination_id' => $package->destination_id,
            'status' => 'completed',
        ]));
    }

    public function test_upcoming_soon_note_is_kept_in_next_step_summary(): void
    {
        Carbon::setTestNow('2026-06-26 10:00:00');
        $this->seed();

        $package = TourPackage::firstOrFail();
        $booking = $this->booking([
            'booking_code' => 'TJ-NEXT-SOON',
            'tour_package_id' => $package->id,
            'destination_id' => $package->destination_id,
            'travel_date' => now()->addDays(3)->toDateString(),
            'status' => 'confirmed',
        ]);

        $this->assertSame('Create payment request', BookingNextStep::label($booking));
        $this->assertStringContainsString('Trip is within 7 days.', BookingNextStep::summary($booking));
    }

    private function assertNextStep(string $label, Booking $booking): void
    {
        $booking->loadMissing('latestPayment');

        $this->assertSame($label, BookingNextStep::label($booking));
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function booking(array $overrides = []): Booking
    {
        return Booking::create([
            'booking_code' => 'TJ-NEXT-'.fake()->unique()->bothify('??????'),
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

    /**
     * @param array<string, mixed> $overrides
     */
    private function payment(Booking $booking, array $overrides = []): BookingPayment
    {
        return BookingPayment::create([
            'booking_id' => $booking->id,
            'provider' => 'midtrans',
            'order_id' => 'TJ-NEXT-PAY-'.fake()->unique()->bothify('??????'),
            'public_token' => str()->random(40),
            'quote_currency' => 'IDR',
            'quote_amount' => 700000,
            'charge_currency' => 'IDR',
            'charge_amount' => 700000,
            'status' => 'pending',
            'snap_token' => 'fake-token',
            'snap_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/fake-token',
            'expires_at' => now()->addDay(),
            ...$overrides,
        ]);
    }
}