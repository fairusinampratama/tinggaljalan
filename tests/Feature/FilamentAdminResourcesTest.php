<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteDetails;
use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Filament\Resources\HeroSlides\Pages\CreateHeroSlide;
use App\Filament\Resources\HeroSlides\Pages\ListHeroSlides;
use App\Filament\Resources\ItineraryItems\ItineraryItemResource;
use App\Filament\Resources\PackageAvailabilities\Pages\ListPackageAvailabilities;
use App\Filament\Resources\PlatformLinks\Pages\CreatePlatformLink;
use App\Filament\Resources\TourPackages\Pages\CreateTourPackage;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\Faq;
use App\Models\HeroSlide;
use App\Models\NewsArticle;
use App\Models\PackageAvailability;
use App\Models\PlatformLink;
use App\Models\Review;
use App\Models\RouteFilter;
use App\Models\SiteSetting;
use App\Models\TourPackage;
use App\Models\TrustStat;
use App\Models\User;
use App\Models\Voucher;
use App\Support\PublicSite;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\FileUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentAdminResourcesTest extends TestCase
{
    use RefreshDatabase;

    public function test_package_owned_itinerary_items_are_hidden_from_navigation(): void
    {
        $this->assertFalse(ItineraryItemResource::shouldRegisterNavigation());
    }

    public function test_tour_package_gallery_enforces_two_to_ten_images_when_used(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@tinggaljalan.test')->firstOrFail();
        $this->actingAs($admin);

        $gallery = Livewire::test(CreateTourPackage::class)
            ->instance()
            ->getSchema('form')
            ?->getComponent('gallery', withHidden: true);

        $this->assertInstanceOf(FileUpload::class, $gallery);
        $this->assertSame(2, $gallery->getMinFiles());
        $this->assertSame(10, $gallery->getMaxFiles());
        $this->assertFalse($gallery->isRequired());
    }

    public function test_hero_slide_allows_image_only_content_with_required_alt_text(): void
    {
        Storage::fake('public');
        $this->seed();
        $admin = User::where('email', 'admin@tinggaljalan.test')->firstOrFail();

        $this->actingAs($admin);
        Livewire::test(CreateHeroSlide::class)
            ->set('data.admin_label', 'Image-only promotion')
            ->set('data.desktop_image', [UploadedFile::fake()->create('hero.jpg', 20, 'image/jpeg')])
            ->set('data.image_alt.us', 'Promotional Bromo landscape')
            ->set('data.text_alignment', 'left')
            ->set('data.focal_position', 'center')
            ->set('data.overlay_strength', 0)
            ->set('data.sort_order', 1)
            ->set('data.is_active', true)
            ->call('create')
            ->assertHasNoFormErrors();

        $slide = HeroSlide::query()->firstOrFail();
        $this->assertSame(['us' => null, 'id' => null, 'cn' => null], $slide->heading);
        $this->assertSame(0, $slide->overlay_strength);
        $this->assertSame('Promotional Bromo landscape', $slide->image_alt['us']);
    }

    public function test_hero_slide_rejects_invalid_content_urls_and_schedule(): void
    {
        Storage::fake('public');
        $this->seed();
        $admin = User::where('email', 'admin@tinggaljalan.test')->firstOrFail();

        $this->actingAs($admin);
        Livewire::test(CreateHeroSlide::class)
            ->set('data.admin_label', 'Invalid promotion')
            ->set('data.desktop_image', [UploadedFile::fake()->create('hero.jpg', 20, 'image/jpeg')])
            ->set('data.image_alt.us', '')
            ->set('data.heading.us', str_repeat('H', 91))
            ->set('data.description.us', str_repeat('D', 241))
            ->set('data.primary_cta_preset', 'custom')
            ->set('data.primary_cta_label.us', 'Book now')
            ->set('data.primary_cta_url', 'javascript:alert(1)')
            ->set('data.has_secondary_cta', true)
            ->set('data.secondary_cta_url', '/routes')
            ->set('data.has_schedule', true)
            ->set('data.start_date', '2026-07-10 12:00:00')
            ->set('data.end_date', '2026-07-09 12:00:00')
            ->set('data.text_alignment', 'left')
            ->set('data.focal_position', 'center')
            ->set('data.overlay_strength', 40)
            ->set('data.sort_order', 1)
            ->set('data.is_active', true)
            ->call('create')
            ->assertHasFormErrors([
                'image_alt.us' => 'required',
                'heading.us' => 'max',
                'description.us' => 'max',
                'primary_cta_url' => 'regex',
                'secondary_cta_label.us' => 'required',
                'end_date' => 'after_or_equal',
            ]);
    }

    public function test_hero_slide_quick_preset_assigns_url_translations_and_next_order(): void
    {
        Storage::fake('public');
        $this->seed();
        HeroSlide::create([
            'admin_label' => 'Existing',
            'desktop_image' => 'admin/hero/existing.jpg',
            'image_alt' => ['us' => 'Existing'],
            'sort_order' => 40,
            'is_active' => false,
        ]);
        $admin = User::where('email', 'admin@tinggaljalan.test')->firstOrFail();

        $this->actingAs($admin);
        Livewire::test(CreateHeroSlide::class)
            ->set('data.admin_label', 'Preset promotion')
            ->set('data.desktop_image', [UploadedFile::fake()->create('hero.jpg', 20, 'image/jpeg')])
            ->set('data.mobile_image', [UploadedFile::fake()->create('hero-mobile.jpg', 20, 'image/jpeg')])
            ->set('data.image_alt.us', 'English description')
            ->set('data.image_alt.id', 'Deskripsi Indonesia')
            ->set('data.heading.cn', '????')
            ->set('data.primary_cta_preset', 'routes')
            ->set('data.text_alignment', 'left')
            ->set('data.focal_position', 'center')
            ->set('data.overlay_strength', 40)
            ->set('data.is_active', true)
            ->call('create')
            ->assertHasNoFormErrors();

        $slide = HeroSlide::query()->where('admin_label', 'Preset promotion')->firstOrFail();
        $this->assertSame('/routes', $slide->primary_cta_url);
        $this->assertSame('Explore routes', $slide->primary_cta_label['us']);
        $this->assertSame('Deskripsi Indonesia', $slide->image_alt['id']);
        $this->assertSame('????', $slide->heading['cn']);
        $this->assertSame(50, $slide->sort_order);
        $this->assertNotNull($slide->mobile_image);
    }

    public function test_hero_slide_preview_warns_when_copy_has_no_overlay(): void
    {
        Storage::fake('public');
        $this->seed();
        $admin = User::where('email', 'admin@tinggaljalan.test')->firstOrFail();

        $this->actingAs($admin);
        Livewire::test(CreateHeroSlide::class)
            ->set('data.admin_label', 'Preview promotion')
            ->set('data.heading.us', 'Readable heading')
            ->set('data.overlay_strength', 0)
            ->assertSee('Text readability may be weak with no overlay');
    }

    public function test_hero_slide_duplicate_is_inactive_unscheduled_and_ordered_last(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@tinggaljalan.test')->firstOrFail();
        $slide = HeroSlide::create([
            'admin_label' => 'Summer promotion',
            'desktop_image' => 'admin/hero/summer.jpg',
            'image_alt' => ['us' => 'Summer'],
            'heading' => ['us' => 'Summer'],
            'sort_order' => 20,
            'is_active' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
        ]);

        $this->actingAs($admin);
        Livewire::test(ListHeroSlides::class)
            ->callTableAction('replicate', $slide)
            ->assertHasNoTableActionErrors();

        $copy = HeroSlide::query()->whereKeyNot($slide->getKey())->firstOrFail();
        $this->assertSame('Summer promotion - Copy', $copy->admin_label);
        $this->assertFalse($copy->is_active);
        $this->assertNull($copy->start_date);
        $this->assertNull($copy->end_date);
        $this->assertGreaterThan($slide->sort_order, $copy->sort_order);
    }

    public function test_hero_slide_listing_supports_legacy_labels_statuses_and_limit_warning(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@tinggaljalan.test')->firstOrFail();

        foreach (range(1, 6) as $index) {
            HeroSlide::create([
                'admin_label' => $index === 1 ? null : "Promotion {$index}",
                'desktop_image' => "admin/hero/{$index}.jpg",
                'image_alt' => ['us' => "Slide {$index}"],
                'heading' => ['us' => "Legacy heading {$index}"],
                'focal_position' => $index === 1 ? 'top' : 'center',
                'sort_order' => $index,
                'is_active' => true,
            ]);
        }

        $legacy = HeroSlide::query()->orderBy('id')->firstOrFail();
        $this->assertSame('Legacy heading 1', $legacy->displayLabel());
        $this->assertSame('Active', $legacy->publicationStatus());

        $this->actingAs($admin)
            ->get("/admin/hero-slides/{$legacy->getKey()}/edit")
            ->assertOk();

        Livewire::test(ListHeroSlides::class)
            ->assertSee('Only the first five in this order appear publicly.');
    }

    public function test_site_details_validates_and_persists_hero_autoplay_settings(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@tinggaljalan.test')->firstOrFail();
        $this->actingAs($admin);

        Livewire::test(SiteDetails::class)
            ->set('data.hero_autoplay_enabled', true)
            ->set('data.hero_autoplay_interval', 4000)
            ->call('save')
            ->assertHasFormErrors(['hero_autoplay_interval' => 'min']);

        Livewire::test(SiteDetails::class)
            ->set('data.hero_autoplay_enabled', true)
            ->set('data.hero_autoplay_interval', 9000)
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = SiteSetting::query()->firstOrFail();
        $this->assertTrue($settings->hero_autoplay_enabled);
        $this->assertSame(9000, $settings->hero_autoplay_interval);
    }

    public function test_availability_rules_have_row_and_bulk_delete_actions(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@tinggaljalan.test')->firstOrFail();
        $record = PackageAvailability::firstOrFail();

        $this->actingAs($admin);
        Livewire::test(ListPackageAvailabilities::class)
            ->assertTableActionExists('delete', record: $record)
            ->assertTableBulkActionExists('delete')
            ->callTableAction('delete', $record);

        $this->assertModelMissing($record);
    }
    public function test_user_resource_creates_admin_accounts(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@tinggaljalan.test')->firstOrFail();

        $this->actingAs($admin);
        Livewire::test(CreateUser::class)
            ->set('data.name', 'Second Admin')
            ->set('data.email', 'second-admin@example.test')
            ->set('data.password', 'strong-test-password')
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'second-admin@example.test',
            'is_admin' => true,
        ]);
    }

    public function test_admin_can_delete_another_admin_but_not_their_own_account(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@tinggaljalan.test')->firstOrFail();
        $other = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin);
        Livewire::test(ListUsers::class)
            ->assertTableActionHidden('delete', $admin)
            ->assertTableActionVisible('delete', $other)
            ->callTableAction('delete', $other);

        $this->assertModelMissing($other);
        $this->assertModelExists($admin);
    }
    public function test_platform_logo_upload_is_stored_and_exposed_publicly(): void
    {
        Storage::fake('public');
        $this->seed();
        PlatformLink::query()->firstOrFail()->update(['is_active' => false]);
        $admin = User::where('email', 'admin@tinggaljalan.test')->firstOrFail();

        $this->actingAs($admin);
        Livewire::test(CreatePlatformLink::class)
            ->set('data.name', 'Uploaded Platform')
            ->set('data.url', 'https://example.test/platform')
            ->set('data.logo', [UploadedFile::fake()->create('platform.png', 10, 'image/png')])
            ->set('data.alt', 'Uploaded Platform logo')
            ->set('data.sort_order', 999)
            ->set('data.is_active', true)
            ->call('create')
            ->assertHasNoFormErrors();

        $platform = PlatformLink::where('name', 'Uploaded Platform')->firstOrFail();
        $this->assertStringStartsWith('admin/platform-links/logos/', $platform->logo);
        Storage::disk('public')->assertExists($platform->logo);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
                ->has('publicData.platformLinks', PlatformLink::MAX_ACTIVE)
                ->where('publicData.platformLinks', fn ($links): bool => collect($links)->contains(
                    fn (array $link): bool => $link['logo'] === '/storage/'.$platform->logo
                        && $link['alt'] === 'Uploaded Platform logo',
                )));
    }
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
            '/admin/hero-slides',
            '/admin/destinations',
            '/admin/route-filters',
            '/admin/tour-packages',
            '/admin/trust-stats',
            '/admin/package-availabilities',
            '/admin/news-articles',
            '/admin/bookings',
            '/admin/vouchers',
            '/admin/faqs',
            '/admin/reviews',
            '/admin/site-details',
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
            '/admin/hero-slides/create',
            '/admin/destinations/create',
            "/admin/destinations/{$destination->getKey()}/edit",
            '/admin/route-filters/create',
            '/admin/route-filters/'.RouteFilter::firstOrFail()->getKey().'/edit',
            '/admin/tour-packages/create',
            '/admin/trust-stats/create',
            '/admin/trust-stats/'.TrustStat::firstOrFail()->getKey().'/edit',
            '/admin/package-availabilities/create',
            '/admin/package-availabilities/'.PackageAvailability::firstOrFail()->getKey().'/edit',
            "/admin/tour-packages/{$package->getKey()}/edit",
            '/admin/vouchers/create',
            '/admin/vouchers/'.Voucher::firstOrFail()->getKey().'/edit',
            '/admin/faqs/create',
            '/admin/faqs/'.Faq::firstOrFail()->getKey().'/edit',
            '/admin/reviews/create',
            '/admin/reviews/'.Review::firstOrFail()->getKey().'/edit',
            '/admin/news-articles/create',
            "/admin/news-articles/{$article->getKey()}/edit",
        ] as $path) {
            $this->actingAs($admin)
                ->get($path)
                ->assertOk();
        }

        $this->actingAs($admin)
            ->get("/admin/bookings/{$booking->getKey()}")
            ->assertNotFound();

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
