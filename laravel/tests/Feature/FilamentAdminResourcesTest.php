<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Destination;
use App\Models\NewsArticle;
use App\Models\RouteFilter;
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
            '/admin/route-filters',
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

    public function test_authenticated_admin_can_access_dashboard_with_operations_widgets(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@tinggaljalan.test')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Today at a glance')
            ->assertSee('Booking action queue')
            ->assertSee('Package readiness');
    }

    public function test_authenticated_non_admin_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
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
            '/admin/route-filters/create',
            '/admin/route-filters/'.RouteFilter::firstOrFail()->getKey(),
            '/admin/route-filters/'.RouteFilter::firstOrFail()->getKey().'/edit',
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

    public function test_seeded_route_filters_are_admin_managed_and_loaded_in_package_form(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@tinggaljalan.test')->firstOrFail();
        $package = TourPackage::firstOrFail();

        $this->assertDatabaseHas('route_filters', [
            'slug' => 'recommended',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get("/admin/tour-packages/{$package->getKey()}/edit")
            ->assertOk()
            ->assertSee('Route filters')
            ->assertSee('Recommended')
            ->assertSee('Family');
    }
}
