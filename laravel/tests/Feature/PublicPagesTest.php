<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\NewsArticle;
use App\Models\PackageAvailability;
use App\Models\PackageAddOn;
use App\Models\RouteFilter;
use App\Models\TourPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_seeded_public_content(): void
    {
        $this->seed();

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('HomePage')
                ->has('featuredRoutes')
                ->has('latestArticles')
                ->has('publicData.site.logoUrl')
                ->has('publicData.site.contactDetails')
                ->has('publicData.home.whyChooseItems')
                ->has('publicData.bookingOptions.paxOptions')
                ->has('publicData.trustStats')
                ->has('publicData.platformLinks')
                ->where('seo.robots', 'index,follow'));
    }

    public function test_routes_listing_filters_and_unknown_slug_redirects(): void
    {
        $this->seed();

        $this->get('/routes?search=bromo&destination=bromo&style=recommended')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('RoutesPage')
                ->where('destinationFilter', 'bromo')
                ->where('search', 'bromo')
                ->has('routes'));

        $this->get('/routes/not-real')
            ->assertRedirect('/routes');
    }

    public function test_routes_listing_uses_admin_managed_route_filters(): void
    {
        $this->seed();

        $package = TourPackage::where('slug', 'bromo-sunrise')->firstOrFail();
        RouteFilter::create([
            'slug' => 'honeymoon',
            'label' => ['us' => 'Honeymoon', 'id' => 'Bulan madu', 'cn' => '蜜月'],
            'description' => ['us' => 'Private trips for couples'],
            'sort_order' => 80,
            'is_active' => true,
        ]);
        $package->update(['styles' => array_values(array_unique([...(array) $package->styles, 'honeymoon']))]);

        $this->get('/routes?style=honeymoon')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('RoutesPage')
                ->where('styleFilter', 'honeymoon')
                ->where('publicData.routeStyles.7.value', 'honeymoon')
                ->where('publicData.routeStyles.7.label.us', 'Honeymoon')
                ->where('routes.0.slug', $package->slug));
    }

    public function test_inactive_route_filters_are_hidden_publicly_and_do_not_break_legacy_package_styles(): void
    {
        $this->seed();

        $package = TourPackage::where('slug', 'bromo-sunrise')->firstOrFail();
        RouteFilter::create([
            'slug' => 'secret',
            'label' => ['us' => 'Secret'],
            'sort_order' => 90,
            'is_active' => false,
        ]);
        $package->update(['styles' => ['recommended', 'secret', 'legacy-only']]);

        $this->get('/routes?style=secret')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('RoutesPage')
                ->where('styleFilter', 'recommended')
                ->where('publicData.routeStyles', fn (array $styles) => collect($styles)->pluck('value')->doesntContain('secret'))
                ->where('routes.0.slug', $package->slug));

        $this->get("/routes/{$package->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('RouteDetailPage')
                ->where('route.slug', $package->slug)
                ->where('route.styles.1', 'secret')
                ->where('route.styles.2', 'legacy-only'));
    }

    public function test_route_detail_renders_booking_cta_and_related_articles(): void
    {
        $this->seed();

        $package = TourPackage::where('slug', 'bromo-sunrise')->firstOrFail();

        $this->get("/routes/{$package->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('RouteDetailPage')
                ->where('route.slug', $package->slug)
                ->has('relatedArticles')
                ->where('seo.og_type', 'product'));
    }

    public function test_route_detail_numeric_id_redirects_to_canonical_slug(): void
    {
        $this->seed();

        $package = TourPackage::where('slug', 'bromo-sunrise')->firstOrFail();

        $this->get("/routes/{$package->id}")
            ->assertRedirect("/routes/{$package->slug}");
    }

    public function test_route_detail_supports_static_and_uploaded_media_paths(): void
    {
        $this->seed();

        $package = TourPackage::where('slug', 'bromo-sunrise')->firstOrFail();
        $package->update([
            'cover_image' => 'images/static-package.jpg',
            'gallery' => ['/images/static-gallery.jpg'],
        ]);

        $this->get("/routes/{$package->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('route.image', '/images/static-package.jpg')
                ->where('route.gallery.0', '/images/static-gallery.jpg'));

        $package->update([
            'cover_image' => 'admin/packages/covers/uploaded-package.jpg',
            'gallery' => ['admin/packages/gallery/uploaded-gallery.jpg'],
        ]);

        $this->get("/routes/{$package->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('route.image', '/storage/admin/packages/covers/uploaded-package.jpg')
                ->where('route.gallery.0', '/storage/admin/packages/gallery/uploaded-gallery.jpg'));
    }

    public function test_route_detail_supports_plain_and_localized_pickup_area_shapes(): void
    {
        $this->seed();

        $package = TourPackage::where('slug', 'bromo-sunrise')->firstOrFail();
        $package->update([
            'pickup_areas' => ['Malang hotel', 'Surabaya airport'],
        ]);

        $this->get("/routes/{$package->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('route.pickupAreas.0', 'Malang hotel')
                ->where('route.pickupDetails.0', 'Malang hotel'));

        $package->update([
            'pickup_areas' => [
                ['us' => 'Malang hotel', 'id' => '', 'cn' => ''],
                ['us' => 'Surabaya airport', 'id' => 'Bandara Surabaya', 'cn' => '泗水机场'],
            ],
            'pickup_label' => ['us' => 'Hotel pickup included', 'id' => '', 'cn' => ''],
            'group_type' => ['us' => 'Private trip', 'id' => '', 'cn' => ''],
        ]);

        $this->get("/routes/{$package->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('route.pickupDetails.0.us', 'Malang hotel')
                ->where('route.pickupDetails.0.id', 'Malang hotel')
                ->where('route.pickupDetails.1.id', 'Bandara Surabaya')
                ->where('route.pickupLabel.us', 'Hotel pickup included')
                ->where('route.pickupLabel.id', 'Hotel pickup included')
                ->where('route.groupType.us', 'Private trip'));
    }

    public function test_route_detail_supports_structured_advanced_policy_and_testimonial_fields(): void
    {
        $this->seed();

        $package = TourPackage::where('slug', 'bromo-sunrise')->firstOrFail();
        $package->update([
            'policies' => [
                'cancellation' => ['us' => 'Cancel up to 24 hours before pickup.', 'id' => '', 'cn' => ''],
                'confirmation' => ['us' => 'Final confirmation is sent by WhatsApp.', 'id' => 'Konfirmasi final via WhatsApp.', 'cn' => ''],
            ],
            'testimonials' => [
                [
                    'name' => 'Ari',
                    'meta' => ['us' => 'Google review', 'id' => '', 'cn' => ''],
                    'quote' => ['us' => 'The trip was organized and easy to follow.', 'id' => '', 'cn' => ''],
                ],
            ],
            'review_source' => ['us' => 'Google reviews', 'id' => '', 'cn' => ''],
        ]);

        $this->get("/routes/{$package->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('route.policies.cancellation.us', 'Cancel up to 24 hours before pickup.')
                ->where('route.policies.cancellation.id', 'Cancel up to 24 hours before pickup.')
                ->where('route.policies.confirmation.id', 'Konfirmasi final via WhatsApp.')
                ->where('route.testimonials.0.name', 'Ari')
                ->where('route.testimonials.0.meta.id', 'Google review')
                ->where('route.testimonials.0.quote.cn', 'The trip was organized and easy to follow.')
                ->where('route.reviewSource.id', 'Google reviews'));
    }

    public function test_route_detail_renders_after_english_primary_package_edit(): void
    {
        $this->seed();

        $package = TourPackage::where('slug', 'bromo-sunrise')->firstOrFail();
        $package->update([
            'title' => ['us' => 'English Primary Bromo Trip', 'id' => '', 'cn' => ''],
            'excerpt' => ['us' => 'Edited from the admin using English first.', 'id' => '', 'cn' => ''],
            'highlights' => [
                ['us' => 'Operator-friendly content entry', 'id' => '', 'cn' => ''],
            ],
            'includes' => [
                ['us' => 'Private pickup', 'id' => '', 'cn' => ''],
            ],
        ]);

        $this->get("/routes/{$package->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('RouteDetailPage')
                ->where('route.title.us', 'English Primary Bromo Trip')
                ->where('route.highlights.0.us', 'Operator-friendly content entry'));
    }

    public function test_news_listing_and_detail_render_with_redirect_for_unknown_slug(): void
    {
        $this->seed();

        $article = NewsArticle::where('slug', 'paket-wisata-bromo-dari-malang')->firstOrFail();

        $this->get('/news?search=bromo&category=travel-guide&destination=bromo')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('NewsPage')
                ->where('search', 'bromo')
                ->where('categoryFilter', 'travel-guide')
                ->where('destinationFilter', 'bromo')
                ->has('articles'));

        $this->get("/news/{$article->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('NewsDetailPage')
                ->where('article.slug', $article->slug)
                ->has('relatedRoutes')
                ->where('seo.og_type', 'article'));

        $this->get('/news/not-real')
            ->assertRedirect('/news');
    }

    public function test_news_detail_supports_static_and_uploaded_media_paths(): void
    {
        $this->seed();

        $article = NewsArticle::where('slug', 'paket-wisata-bromo-dari-malang')->firstOrFail();
        $article->update(['cover_image' => 'images/static-news.jpg']);

        $this->get("/news/{$article->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('article.coverImage', '/images/static-news.jpg'));

        $article->update(['cover_image' => 'admin/news/covers/uploaded-news.jpg']);

        $this->get("/news/{$article->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('article.coverImage', '/storage/admin/news/covers/uploaded-news.jpg'));
    }

    public function test_booking_draft_persists_and_submission_creates_booking(): void
    {
        $this->seed();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

        $package = TourPackage::where('slug', 'bromo-sunrise')->with('packageAddOns')->firstOrFail();
        $packageAddOn = $package->packageAddOns->firstOrFail();

        $this->post('/booking', [
            'route' => $package->slug,
            'date' => '2026-06-25',
            'pax' => 2,
            'pickup' => 'Malang Hotel',
            'traveler_type' => 'local',
            'currency' => 'IDR',
            'add_ons' => [(string) $packageAddOn->id],
            'voucher' => 'BROMO10',
        ])->assertRedirect('/checkout/review');

        $this->get('/checkout/review')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('CheckoutReviewPage')
                ->where('booking.draft.pickup', 'Malang Hotel'));

        $this->post('/checkout/review', [
            'name' => 'Traveler Test',
            'whatsapp' => '+628111111111',
            'email' => 'traveler@example.test',
            'voucher' => 'BROMO10',
            'notes' => 'Need sunrise pickup',
        ])->assertRedirect('/checkout/confirmation');

        $this->assertDatabaseHas('bookings', [
            'name' => 'Traveler Test',
            'tour_package_id' => $package->id,
            'status' => 'new',
        ]);
        $this->assertNotNull(Booking::where('name', 'Traveler Test')->first()?->selected_add_ons);
    }

    public function test_booking_recalculation_uses_database_vouchers_prices_add_ons_and_availability(): void
    {
        $this->seed();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

        $package = TourPackage::where('slug', 'bromo-sunrise')->with(['packageAddOns', 'destination'])->firstOrFail();
        $packageAddOn = $package->packageAddOns->firstOrFail();

        PackageAvailability::updateOrCreate(
            [
                'tour_package_id' => $package->id,
                'destination_id' => null,
                'date' => '2026-08-01',
            ],
            [
                'status' => 'limited',
                'seats_left' => 2,
                'reason' => 'Test DB availability rule',
            ],
        );

        $this->post('/booking/recalculate', [
            'route' => $package->slug,
            'date' => '2026-08-01',
            'pax' => 2,
            'pickup' => 'Malang Hotel',
            'traveler_type' => 'local',
            'currency' => 'IDR',
            'add_ons' => [(string) $packageAddOn->id],
            'voucher' => 'BROMO10',
        ])->assertRedirect();

        $this->get('/booking')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('BookingPage')
                ->where('booking.draft.route', $package->slug)
                ->where('booking.availability.status', 'limited')
                ->where('booking.availability.seatsLeft', 2)
                ->where('booking.summary.voucher.code', 'BROMO10')
                ->has('booking.summary.addOns.0')
                ->where('booking.summary.addOns.0.priceIdr', $packageAddOn->price_idr)
                ->where('booking.summary.currency', 'IDR'));
    }

    public function test_booking_uses_route_specific_add_on_prices_for_the_same_add_on(): void
    {
        $this->seed();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

        $firstPackage = TourPackage::where('slug', 'bromo-sunrise')->with('packageAddOns')->firstOrFail();
        $secondPackage = TourPackage::where('slug', 'bromo-madakaripura')->firstOrFail();
        $firstAddOn = $firstPackage->packageAddOns->firstOrFail();

        $firstAddOn = PackageAddOn::updateOrCreate(
            [
                'tour_package_id' => $firstPackage->id,
                'source_key' => 'test-route-guide',
            ],
            [
                'title' => ['us' => 'Route guide'],
                'description' => ['us' => 'Route-specific guide add-on.'],
                'price_idr' => 100000,
                'price_usd' => 7,
                'pricing_type' => 'per_booking',
                'sort_order' => 1,
                'is_active' => true,
            ],
        );

        $secondAddOn = PackageAddOn::updateOrCreate(
            [
                'tour_package_id' => $secondPackage->id,
                'source_key' => 'test-route-guide',
            ],
            [
                'title' => ['us' => 'Route guide'],
                'description' => ['us' => 'Route-specific guide add-on.'],
                'price_idr' => 250000,
                'price_usd' => 17,
                'pricing_type' => 'per_booking',
                'sort_order' => 1,
                'is_active' => true,
            ],
        );

        $this->post('/booking/recalculate', [
            'route' => $firstPackage->slug,
            'date' => '2026-08-01',
            'pax' => 1,
            'pickup' => 'Malang Hotel',
            'traveler_type' => 'local',
            'currency' => 'IDR',
            'add_ons' => [(string) $firstAddOn->id],
            'voucher' => '',
        ])->assertRedirect();

        $this->get('/booking')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('booking.summary.addOns.0.priceIdr', 100000)
                ->where('booking.summary.addOnsTotal', 100000));

        $this->post('/booking/recalculate', [
            'route' => $secondPackage->slug,
            'date' => '2026-08-01',
            'pax' => 1,
            'pickup' => 'Malang Hotel',
            'traveler_type' => 'local',
            'currency' => 'IDR',
            'add_ons' => [(string) $secondAddOn->id],
            'voucher' => '',
        ])->assertRedirect();

        $this->get('/booking')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('booking.summary.addOns.0.priceIdr', 250000)
                ->where('booking.summary.addOnsTotal', 250000));
    }

    public function test_public_frontend_imports_only_translation_constants_from_js_data(): void
    {
        $files = collect(File::allFiles(resource_path('js')))
            ->filter(fn ($file) => $file->getExtension() === 'jsx' || $file->getExtension() === 'js');

        $matches = $files->flatMap(function ($file) {
            preg_match_all('/(?:from\s+[\'"][^\'"]*data\/([^\'"]+)[\'"]|import\s+[\'"][^\'"]*data\/([^\'"]+)[\'"])/', $file->getContents(), $found);

            return collect(array_merge($found[1] ?? [], $found[2] ?? []))
                ->filter()
                ->map(fn ($module) => basename($module));
        })->unique()->values()->all();

        $this->assertSame(['translations'], $matches);
    }
}
