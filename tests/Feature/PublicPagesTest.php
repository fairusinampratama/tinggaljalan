<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Destination;
use App\Models\Faq;
use App\Models\HeroSlide;
use App\Models\NewsArticle;
use App\Models\PackageAvailability;
use App\Models\PackageAddOn;
use App\Models\RouteFilter;
use App\Models\SiteSetting;
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
                ->where('publicData.bookingOptions.paxMin', 1)
                ->where('publicData.bookingOptions.paxMax', 999)
                ->where('publicData.bookingOptions.largeGroupThreshold', 10)
                ->has('publicData.trustStats')
                ->has('publicData.platformLinks')
                ->where('seo.robots', 'index,follow'));
    }

    public function test_homepage_serializes_only_the_first_five_ordered_current_hero_slides(): void
    {
        $this->travelTo(now()->setDate(2026, 7, 7)->setTime(12, 0));

        SiteSetting::create([
            'hero_autoplay_enabled' => true,
            'hero_autoplay_interval' => 12000,
        ]);

        foreach (range(1, 6) as $index) {
            HeroSlide::create([
                'desktop_image' => "admin/hero/desktop-{$index}.jpg",
                'mobile_image' => $index === 1 ? 'admin/hero/mobile-1.jpg' : null,
                'image_alt' => ['us' => "Slide {$index}", 'id' => "Slide ID {$index}"],
                'heading' => ['us' => "Heading {$index}"],
                'sort_order' => $index,
                'is_active' => true,
                'start_date' => $index === 1 ? now() : now()->subDay(),
                'end_date' => $index === 1 ? now() : now()->addDay(),
            ]);
        }

        HeroSlide::create([
            'desktop_image' => 'admin/hero/inactive.jpg',
            'image_alt' => ['us' => 'Inactive'],
            'sort_order' => 0,
            'is_active' => false,
        ]);
        HeroSlide::create([
            'desktop_image' => 'admin/hero/future.jpg',
            'image_alt' => ['us' => 'Future'],
            'sort_order' => 0,
            'is_active' => true,
            'start_date' => now()->addMinute(),
        ]);
        HeroSlide::create([
            'desktop_image' => 'admin/hero/ended.jpg',
            'image_alt' => ['us' => 'Ended'],
            'sort_order' => 0,
            'is_active' => true,
            'end_date' => now()->subMinute(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('publicData.home.heroSlides', 5)
                ->where('publicData.home.heroSlides.0.desktopImage', '/storage/admin/hero/desktop-1.jpg')
                ->where('publicData.home.heroSlides.0.mobileImage', '/storage/admin/hero/mobile-1.jpg')
                ->where('publicData.home.heroSlides.0.imageAlt.cn', 'Slide 1')
                ->where('publicData.home.heroSlides.4.heading.us', 'Heading 5')
                ->where('publicData.home.heroSettings.autoplayEnabled', true)
                ->where('publicData.home.heroSettings.autoplayInterval', 12000));
    }

    public function test_homepage_exposes_empty_hero_slides_and_safe_default_settings_for_frontend_fallback(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('publicData.home.heroSlides', 0)
                ->where('publicData.home.heroSettings.autoplayEnabled', false)
                ->where('publicData.home.heroSettings.autoplayInterval', 8000));
    }

    public function test_homepage_clamps_out_of_range_hero_autoplay_interval(): void
    {
        SiteSetting::create([
            'hero_autoplay_enabled' => true,
            'hero_autoplay_interval' => 25000,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('publicData.home.heroSettings.autoplayInterval', 15000));
    }

    public function test_homepage_only_promotes_active_featured_destinations(): void
    {
        $this->seed();

        $destination = Destination::create([
            'slug' => 'not-featured',
            'name' => 'Not Featured',
            'is_active' => true,
            'is_featured' => false,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('destinations', fn ($destinations): bool => collect($destinations)
                    ->doesntContain('slug', $destination->slug))
                ->where('publicData.destinations', fn ($destinations): bool => collect($destinations)
                    ->contains('slug', $destination->slug)));
    }
    public function test_homepage_and_route_pages_use_the_same_global_active_faqs(): void
    {
        $this->seed();

        $package = TourPackage::where('slug', 'bromo-sunrise')->firstOrFail();
        Faq::create([
            'question' => ['us' => 'Global Scope Test'],
            'answer' => ['us' => 'Global answer'],
            'sort_order' => 0,
            'is_active' => true,
        ]);
        Faq::create([
            'question' => ['us' => 'Inactive Scope Test'],
            'answer' => ['us' => 'Hidden answer'],
            'sort_order' => 0,
            'is_active' => false,
        ]);

        $assertGlobalFaqs = function ($faqs): bool {
            $questions = collect($faqs)->pluck('question.us');

            return $questions->contains('Global Scope Test')
                && ! $questions->contains('Inactive Scope Test');
        };

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('faqs', $assertGlobalFaqs)
                ->where('publicData.faqs', $assertGlobalFaqs));

        $this->get('/routes/'.$package->slug)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('faqs', $assertGlobalFaqs));
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
            'label' => ['us' => 'Honeymoon', 'id' => 'Bulan madu', 'cn' => 'èœœæœˆ'],
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
                ->where('publicData.routeStyles', fn ($styles) => collect($styles)->pluck('value')->doesntContain('secret'))
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
                ['us' => 'Surabaya airport', 'id' => 'Bandara Surabaya', 'cn' => 'æ³—æ°´æœºåœº'],
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

    public function test_booking_starts_without_an_automatically_applied_voucher(): void
    {
        $this->seed();

        $this->withSession(['booking_draft' => [
            'voucher' => 'BROMO10',
        ]])
            ->get('/booking')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('booking.draft.voucher', '')
                ->where('booking.summary.voucher', null)
                ->where('booking.summary.discount', 0));
    }

    public function test_booking_draft_persists_and_submission_creates_booking(): void
    {
        $this->seed();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

        $package = TourPackage::where('slug', 'bromo-sunrise')->with('packageAddOns')->firstOrFail();
        $packageAddOn = $package->packageAddOns->firstOrFail();

        $this->withSession(['language' => 'cn'])->post('/booking', [
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
            'whatsapp' => '08111111111',
            'whatsapp_country' => 'ID',
            'email' => 'TRAVELER@EXAMPLE.TEST',
            'voucher' => 'BROMO10',
            'notes' => 'Need sunrise pickup',
        ])->assertRedirect('/checkout/confirmation');

        $this->assertDatabaseHas('bookings', [
            'name' => 'Traveler Test',
            'tour_package_id' => $package->id,
            'status' => 'new',
            'whatsapp' => '+628111111111',
            'whatsapp_country' => 'ID',
            'email' => 'traveler@example.test',
            'communication_language' => 'cn',
        ]);
        $this->assertNotNull(Booking::where('name', 'Traveler Test')->first()?->selected_add_ons);

        $this->get('/checkout/confirmation')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('language', 'cn'));
    }

    public function test_limited_capacity_warns_but_allows_booking_request(): void
    {
        $this->seed();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

        $package = TourPackage::where('slug', 'bromo-sunrise')->firstOrFail();
        PackageAvailability::create([
            'tour_package_id' => $package->id,
            'date' => '2030-08-20',
            'status' => 'limited',
            'seats_left' => 2,
            'reason' => 'Only one vehicle remains',
        ]);

        $this->post('/booking', [
            'route' => $package->slug,
            'date' => '2030-08-20',
            'pax' => 5,
            'pickup' => 'Malang Hotel',
            'traveler_type' => 'international',
            'currency' => 'USD',
            'add_ons' => [],
        ])->assertRedirect('/checkout/review');

        $this->get('/checkout/review')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('booking.availability.status', 'limited')
                ->where('booking.availability.seatsLeft', 2)
                ->where('booking.availability.capacityExceeded', true));

        $this->post('/checkout/review', [
            'name' => 'Capacity Warning Test',
            'whatsapp' => '08111111111',
            'whatsapp_country' => 'ID',
            'email' => 'capacity@example.test',
        ])->assertRedirect('/checkout/confirmation');

        $this->assertDatabaseHas('bookings', [
            'name' => 'Capacity Warning Test',
            'tour_package_id' => $package->id,
            'pax' => 5,
            'status' => 'new',
        ]);
    }
    public function test_booking_accepts_large_group_guest_count_within_configured_limit(): void
    {
        $this->seed();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $package = TourPackage::where('slug', 'bromo-sunrise')->firstOrFail();
        $package->update(['pricing_mode' => 'flat']);
        $package->priceTiers()->delete();

        $payload = [
            'route' => $package->slug,
            'date' => '2026-08-01',
            'pax' => 100,
            'pickup' => 'Malang Hotel',
            'traveler_type' => 'international',
            'currency' => 'USD',
            'add_ons' => [],
        ];

        $this->post('/booking', $payload)->assertRedirect('/checkout/review');
        $this->assertSame(100, session('booking_draft.pax'));

        $this->post('/booking', [...$payload, 'pax' => 1000])
            ->assertSessionHasErrors('pax');
    }
    public function test_public_booking_stops_when_group_exceeds_final_price_tier(): void
    {
        $this->seed();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

        $package = TourPackage::where('slug', 'bromo-sunrise')->firstOrFail();
        $package->update(['pricing_mode' => 'tiered']);
        $package->priceTiers()->delete();
        $package->priceTiers()->createMany([
            ['min_pax' => 1, 'max_pax' => 2, 'price_idr' => 500000, 'price_usd' => 35, 'sort_order' => 1],
            ['min_pax' => 3, 'max_pax' => 5, 'price_idr' => 450000, 'price_usd' => 32, 'sort_order' => 2],
        ]);

        $payload = [
            'route' => $package->slug,
            'date' => '2026-08-01',
            'pax' => 6,
            'pickup' => 'Malang Hotel',
            'traveler_type' => 'international',
            'currency' => 'USD',
            'add_ons' => [],
        ];

        $this->post('/booking', $payload)
            ->assertRedirect('/booking')
            ->assertSessionHasErrors('pax');

        $this->assertDatabaseMissing('bookings', [
            'tour_package_id' => $package->id,
            'pax' => 6,
        ]);

    }

    public function test_booking_submission_requires_valid_email_and_whatsapp(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

        $this->post('/checkout/review', [
            'name' => 'Invalid Contact',
            'whatsapp' => '123',
            'whatsapp_country' => 'ID',
            'email' => '',
        ])->assertSessionHasErrors(['whatsapp', 'email']);
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

    public function test_traveler_type_selects_price_list_and_ignores_submitted_currency(): void
    {
        $this->seed();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

        $package = TourPackage::where('slug', 'bromo-sunrise')->firstOrFail();
        $basePayload = [
            'route' => $package->slug,
            'date' => '2030-08-01',
            'pax' => 2,
            'pickup' => 'Malang Hotel',
            'add_ons' => [],
            'voucher' => '',
        ];

        $this->post('/booking/recalculate', $basePayload + [
            'traveler_type' => 'local',
            'currency' => 'USD',
        ])->assertRedirect();

        $this->get('/booking')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->missing('publicData.bookingOptions.currencyOptions')
                ->where('booking.draft.travelerType', 'local')
                ->where('booking.draft.currency', 'IDR')
                ->where('booking.summary.currency', 'IDR')
                ->where('booking.summary.base', $package->base_price_idr)
                ->where('booking.summary.total', $package->base_price_idr * 2));

        $this->post('/booking/recalculate', $basePayload + [
            'traveler_type' => 'international',
            'currency' => 'IDR',
        ])->assertRedirect();

        $this->get('/booking')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('booking.draft.travelerType', 'international')
                ->where('booking.draft.currency', 'USD')
                ->where('booking.summary.currency', 'USD')
                ->where('booking.summary.base', $package->base_price_usd)
                ->where('booking.summary.total', $package->base_price_usd * 2));
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
