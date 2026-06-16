<?php

namespace Tests\Feature;

use App\Models\AddOn;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\Faq;
use App\Models\NewsArticle;
use App\Models\PackageAvailability;
use App\Models\PlatformLink;
use App\Models\Review;
use App\Models\Setting;
use App\Models\TourPackage;
use App\Models\TrustStat;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TravelContentFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_create_the_core_travel_content_graph(): void
    {
        $this->seed();

        $this->assertDatabaseCount('destinations', 5);
        $this->assertDatabaseCount('tour_packages', 20);
        $this->assertDatabaseCount('news_articles', 6);
        $this->assertDatabaseCount('faqs', 12);
        $this->assertDatabaseCount('platform_links', 4);
        $this->assertDatabaseCount('vouchers', 2);
        $this->assertDatabaseHas('users', ['email' => 'admin@tinggaljalan.test']);

        $bromo = Destination::where('slug', 'bromo')->firstOrFail();
        $this->assertGreaterThan(0, $bromo->tourPackages()->count());

        $package = TourPackage::where('slug', 'bromo-sunrise')->firstOrFail();
        $this->assertTrue($package->destination->is($bromo));
        $this->assertGreaterThan(0, $package->itineraryItems()->count());
        $this->assertGreaterThan(0, $package->addOns()->count());
        $this->assertSame('Bromo Sunrise Private Trip', $package->title['us']);

        $article = NewsArticle::where('slug', 'paket-wisata-bromo-dari-malang')->firstOrFail();
        $this->assertSame('published', $article->status);
        $this->assertGreaterThan(0, $article->tourPackages()->count());
    }

    public function test_content_scopes_and_supporting_tables_work(): void
    {
        $this->seed();

        $this->assertSame(4, Destination::active()->featured()->count());
        $this->assertSame('bromo', Destination::active()->ordered()->first()->slug);
        $this->assertSame(6, NewsArticle::published()->count());
        $this->assertGreaterThan(0, PackageAvailability::where('status', 'limited')->count());
        $this->assertSame(4, TrustStat::active()->count());
        $this->assertSame(3, Review::featured()->active()->count());
        $this->assertNotNull(Setting::where('group', 'site')->where('key', 'contact_details')->first());
        $this->assertNotNull(PlatformLink::active()->where('name', 'Traveloka')->first());
        $this->assertNotNull(Voucher::active()->where('code', 'BROMO10')->first());
        $this->assertNotNull(Faq::active()->where('placement', 'general')->first());
    }

    public function test_booking_can_store_an_inquiry_snapshot(): void
    {
        $this->seed();

        $package = TourPackage::where('slug', 'bromo-sunrise')->firstOrFail();
        $addOn = AddOn::where('slug', 'local-guide')->firstOrFail();

        $booking = Booking::create([
            'booking_code' => 'TJ-TEST-001',
            'tour_package_id' => $package->id,
            'destination_id' => $package->destination_id,
            'name' => 'Test Traveler',
            'email' => 'traveler@example.test',
            'whatsapp' => '+628111111111',
            'travel_date' => '2026-06-25',
            'pax' => 2,
            'pickup' => 'Malang Hotel Area',
            'traveler_type' => 'local',
            'currency' => 'IDR',
            'selected_add_ons' => [
                [
                    'slug' => $addOn->slug,
                    'title' => $addOn->title,
                    'price_idr' => $addOn->price_idr,
                ],
            ],
            'voucher_code' => 'BROMO10',
            'subtotal' => 700000,
            'discount_total' => 70000,
            'total' => 630000,
            'payment_gateway' => 'Midtrans',
            'status' => 'new',
        ]);

        $this->assertSame('TJ-TEST-001', $booking->booking_code);
        $this->assertSame('local-guide', $booking->selected_add_ons[0]['slug']);
        $this->assertTrue($booking->tourPackage->is($package));
        $this->assertSame(630000, $booking->total);
    }
}
