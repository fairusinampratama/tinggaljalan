<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Destination;
use App\Models\NewsArticle;
use App\Models\TourPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentAdminResourcesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_resources(): void
    {
        $this->get('/admin/destinations')
            ->assertRedirect('/admin/login');
    }

    public function test_authenticated_admin_can_access_core_resource_indexes(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@tinggaljalan.test')->firstOrFail();

        foreach ([
            '/admin/destinations',
            '/admin/tour-packages',
            '/admin/news-articles',
            '/admin/bookings',
            '/admin/faqs',
            '/admin/settings',
            '/admin/users',
        ] as $path) {
            $this->actingAs($admin)
                ->get($path)
                ->assertOk();
        }
    }

    public function test_authenticated_admin_can_access_core_create_and_edit_pages(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@tinggaljalan.test')->firstOrFail();
        $destination = Destination::firstOrFail();
        $package = TourPackage::firstOrFail();
        $article = NewsArticle::firstOrFail();
        $booking = Booking::create([
            'booking_code' => 'TJ-ADMIN-TEST',
            'tour_package_id' => $package->id,
            'destination_id' => $destination->id,
            'name' => 'Admin Test',
            'email' => 'admin-test@example.test',
            'whatsapp' => '+628111111111',
            'travel_date' => '2026-06-25',
            'pax' => 2,
            'pickup' => 'Malang',
            'traveler_type' => 'local',
            'currency' => 'IDR',
            'subtotal' => 700000,
            'discount_total' => 0,
            'total' => 700000,
            'status' => 'new',
        ]);

        foreach ([
            '/admin/destinations/create',
            "/admin/destinations/{$destination->getKey()}/edit",
            '/admin/tour-packages/create',
            "/admin/tour-packages/{$package->getKey()}/edit",
            '/admin/news-articles/create',
            "/admin/news-articles/{$article->getKey()}/edit",
            '/admin/bookings/create',
            "/admin/bookings/{$booking->getKey()}/edit",
        ] as $path) {
            $this->actingAs($admin)
                ->get($path)
                ->assertOk();
        }
    }
}
