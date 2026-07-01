<?php

namespace Tests\Feature;

use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\NewsArticle;
use App\Models\RouteFilter;
use App\Models\TourPackage;
use App\Models\User;
use App\Support\PublicSite;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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
            '/admin/payment-settings',
            '/admin/email-gateway-settings',
            '/admin/whatsapp-gateway-settings',
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
            "/admin/bookings/{$booking->getKey()}",
        ] as $path) {
            $this->actingAs($admin)
                ->get($path)
                ->assertOk();
        }

        $this->actingAs($admin)
            ->get('/admin/bookings/create')
            ->assertNotFound();

        $this->actingAs($admin)
            ->get("/admin/bookings/{$booking->getKey()}/edit")
            ->assertNotFound();
    }

    public function test_admin_can_correct_booking_details_without_mutating_commercial_or_workflow_fields(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@tinggaljalan.test')->firstOrFail();
        $package = TourPackage::firstOrFail();
        $booking = Booking::create([
            'booking_code' => 'TJ-CORRECT-TEST',
            'tour_package_id' => $package->id,
            'destination_id' => $package->destination_id,
            'name' => 'Original Traveler',
            'email' => 'original@example.test',
            'whatsapp' => '+628111111111',
            'travel_date' => now()->addWeeks(2)->toDateString(),
            'pax' => 3,
            'pickup' => 'Original pickup',
            'traveler_type' => 'international',
            'currency' => 'USD',
            'selected_add_ons' => [['title' => 'Private guide']],
            'voucher_code' => 'BROMO10',
            'subtotal' => 150,
            'discount_total' => 10,
            'total' => 140,
            'payment_gateway' => 'midtrans',
            'notes' => 'Original note',
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
        $protected = $booking->only([
            'tour_package_id',
            'destination_id',
            'pax',
            'traveler_type',
            'currency',
            'selected_add_ons',
            'voucher_code',
            'subtotal',
            'discount_total',
            'total',
            'payment_gateway',
            'status',
            'confirmed_at',
        ]);

        $this->actingAs($admin);
        Livewire::test(ListBookings::class)
            ->mountAction(TestAction::make('correct_details')->table($booking))
            ->set('mountedActions.0.data', [
                'name' => 'Corrected Traveler',
                'whatsapp_country' => 'ID',
                'whatsapp' => '0812 3456 7890',
                'email' => 'CORRECTED@EXAMPLE.TEST',
                'travel_date' => now()->addWeeks(3)->toDateString(),
                'pickup' => 'Corrected pickup',
                'notes' => 'Corrected internal note',
            ])
            ->assertSchemaStateSet(['name' => 'Corrected Traveler'])
            ->callMountedAction()
            ->assertHasNoActionErrors()
            ->assertNotified('Booking details corrected');

        $booking->refresh();
        $this->assertSame('Corrected Traveler', $booking->name);
        $this->assertSame('+6281234567890', $booking->whatsapp);
        $this->assertSame('ID', $booking->whatsapp_country);
        $this->assertSame('corrected@example.test', $booking->email);
        $this->assertSame('Corrected pickup', $booking->pickup);
        $this->assertSame('Corrected internal note', $booking->notes);

        foreach ($protected as $field => $value) {
            $this->assertEquals($value, $booking->{$field}, "Protected field [{$field}] was changed.");
        }
    }

    public function test_booking_inbox_defaults_to_needs_action_and_uses_compact_searchable_columns(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@tinggaljalan.test')->firstOrFail();
        $package = TourPackage::firstOrFail();
        $booking = Booking::create([
            'booking_code' => 'TJ-INBOX-SEARCH',
            'tour_package_id' => $package->id,
            'destination_id' => $package->destination_id,
            'name' => 'Unique Inbox Traveler',
            'email' => 'unique-inbox@example.test',
            'whatsapp' => '+628777777777',
            'travel_date' => now()->addWeeks(2),
            'pax' => 3,
            'traveler_type' => 'international',
            'currency' => 'IDR',
            'subtotal' => 500000,
            'discount_total' => 0,
            'total' => 500000,
            'status' => 'new',
        ]);

        $this->actingAs($admin);

        $component = Livewire::test(ListBookings::class)
            ->assertSet('activeTab', 'needs_action')
            ->assertCanSeeTableRecords([$booking])
            ->assertTableColumnVisible('booking_summary')
            ->assertTableColumnVisible('customer_summary')
            ->assertTableColumnVisible('trip_summary')
            ->assertTableColumnVisible('workflow')
            ->assertTableColumnVisible('payment_summary')
            ->assertTableColumnExists('status', fn ($column): bool => $column->isToggleable() && $column->isToggledHiddenByDefault())
            ->assertTableColumnExists('raw_payment_status', fn ($column): bool => $column->isToggleable() && $column->isToggledHiddenByDefault())
            ->assertTableColumnExists('handoff_status', fn ($column): bool => $column->isToggleable() && $column->isToggledHiddenByDefault())
            ->assertTableColumnExists('destination.name', fn ($column): bool => $column->isToggleable() && $column->isToggledHiddenByDefault())
            ->assertTableColumnExists('updated_at', fn ($column): bool => $column->isToggleable() && $column->isToggledHiddenByDefault())
            ->assertTableFilterExists('status')
            ->assertTableFilterExists('destination_id')
            ->assertTableFilterExists('travel_date');

        $component
            ->searchTable('Unique Inbox Traveler')
            ->assertCanSeeTableRecords([$booking])
            ->searchTable(PublicSite::localized($package->title, 'us', $package->slug))
            ->assertCanSeeTableRecords([$booking]);

        Livewire::withQueryParams(['tab' => 'needs_attention'])
            ->test(ListBookings::class)
            ->assertSet('activeTab', 'needs_action');
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
