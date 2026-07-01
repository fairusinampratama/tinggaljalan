<?php

namespace Tests\Unit;

use App\Filament\Support\BookingWorkflow;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Destination;
use App\Models\TourPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-07-01 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_workflow_stages_and_categories_match_operational_rules(): void
    {
        $closed = $this->booking(['status' => 'completed']);
        $missingContact = $this->booking(['email' => null, 'whatsapp' => null]);
        $missingTrip = $this->booking(['tour_package_id' => null]);
        $new = $this->booking(['status' => 'new']);
        $past = $this->booking(['travel_date' => now()->subDay()]);
        $withoutPayment = $this->booking();
        $unsent = $this->booking();
        $awaiting = $this->booking();
        $failed = $this->booking();
        $receiptFailed = $this->booking();
        $paidUpcoming = $this->booking(['travel_date' => now()->addDays(3)]);

        $this->payment($unsent, ['status' => 'pending']);
        $this->payment($awaiting, ['status' => 'invoice_sent', 'sent_at' => now()]);
        $this->payment($failed, ['status' => 'expired', 'expired_at' => now()]);
        $this->payment($receiptFailed, ['status' => 'paid', 'paid_at' => now(), 'receipt_email_failed_at' => now()]);
        $this->payment($paidUpcoming, ['status' => 'paid', 'paid_at' => now()]);

        $expectations = [
            [$closed, 'Closed', BookingWorkflow::CLOSED],
            [$missingContact, 'Add contact details', BookingWorkflow::NEEDS_ACTION],
            [$missingTrip, 'Complete trip details', BookingWorkflow::NEEDS_ACTION],
            [$new, 'Confirm availability', BookingWorkflow::NEEDS_ACTION],
            [$past, 'Mark trip completed', BookingWorkflow::NEEDS_ACTION],
            [$withoutPayment, 'Create payment request', BookingWorkflow::NEEDS_ACTION],
            [$unsent, 'Send payment request', BookingWorkflow::NEEDS_ACTION],
            [$awaiting, 'Awaiting payment', BookingWorkflow::AWAITING_PAYMENT],
            [$failed, 'Resolve payment issue', BookingWorkflow::NEEDS_ACTION],
            [$receiptFailed, 'Resolve payment issue', BookingWorkflow::NEEDS_ACTION],
            [$paidUpcoming, 'Prepare trip', BookingWorkflow::CONFIRMED_TRIPS],
        ];

        foreach ($expectations as [$booking, $label, $category]) {
            $booking->unsetRelation('latestPayment');
            $this->assertSame($label, BookingWorkflow::label($booking));
            $this->assertSame($category, BookingWorkflow::category($booking));
        }

        $this->assertStringContainsString('Trip is within 7 days.', BookingWorkflow::summary($paidUpcoming));
    }

    public function test_non_all_tabs_are_mutually_exclusive_and_match_classifier(): void
    {
        $needs = $this->booking(['status' => 'new']);
        $awaiting = $this->booking();
        $confirmed = $this->booking();
        $closed = $this->booking(['status' => 'cancelled']);

        $this->payment($awaiting, ['status' => 'invoice_sent', 'whatsapp_sent_at' => now()]);
        $this->payment($confirmed, ['status' => 'paid', 'paid_at' => now()]);

        $categories = [
            BookingWorkflow::NEEDS_ACTION,
            BookingWorkflow::AWAITING_PAYMENT,
            BookingWorkflow::CONFIRMED_TRIPS,
            BookingWorkflow::CLOSED,
        ];
        $memberships = [];

        foreach ($categories as $category) {
            $ids = BookingWorkflow::applyCategory(Booking::query(), $category)->pluck('id')->all();

            foreach ($ids as $id) {
                $memberships[$id][] = $category;
            }
        }

        foreach ([$needs, $awaiting, $confirmed, $closed] as $booking) {
            $this->assertSame([BookingWorkflow::category($booking)], $memberships[$booking->id] ?? []);
        }
    }

    private function booking(array $overrides = []): Booking
    {
        $destination = Destination::firstOrCreate(['slug' => 'workflow-bromo'], ['name' => 'Bromo']);
        $package = TourPackage::firstOrCreate(['slug' => 'workflow-bromo-trip'], [
            'destination_id' => $destination->id,
            'title' => ['us' => 'Workflow Bromo Trip'],
            'base_price_idr' => 500000,
            'base_price_usd' => 35,
            'is_active' => true,
        ]);

        return Booking::create(array_merge([
            'booking_code' => 'TJ-WORKFLOW-'.strtoupper(fake()->unique()->bothify('??????')),
            'tour_package_id' => $package->id,
            'destination_id' => $destination->id,
            'name' => 'Workflow Test',
            'email' => 'workflow@example.test',
            'whatsapp' => '+6281234567890',
            'travel_date' => now()->addWeeks(2),
            'pax' => 2,
            'traveler_type' => 'international',
            'currency' => 'IDR',
            'subtotal' => 500000,
            'discount_total' => 0,
            'total' => 500000,
            'status' => 'confirmed',
        ], $overrides));
    }

    private function payment(Booking $booking, array $overrides = []): BookingPayment
    {
        return BookingPayment::create(array_merge([
            'booking_id' => $booking->id,
            'provider' => 'midtrans',
            'order_id' => 'TJ-WORKFLOW-PAY-'.strtoupper(fake()->unique()->bothify('??????')),
            'public_token' => fake()->unique()->regexify('[A-Za-z0-9]{40}'),
            'quote_currency' => 'IDR',
            'quote_amount' => 500000,
            'charge_currency' => 'IDR',
            'charge_amount' => 500000,
            'status' => 'pending',
        ], $overrides));
    }
}
