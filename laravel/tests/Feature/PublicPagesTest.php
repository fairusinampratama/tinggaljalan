<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\NewsArticle;
use App\Models\PackageAvailability;
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

        $package = TourPackage::where('slug', 'bromo-sunrise')->with('addOns')->firstOrFail();

        $this->post('/booking', [
            'route' => $package->slug,
            'date' => '2026-06-25',
            'pax' => 2,
            'pickup' => 'Malang Hotel',
            'traveler_type' => 'local',
            'currency' => 'IDR',
            'add_ons' => [$package->addOns->first()?->slug],
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

        $package = TourPackage::where('slug', 'bromo-sunrise')->with(['addOns', 'destination'])->firstOrFail();
        $addOn = $package->addOns->firstOrFail();

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
            'add_ons' => [$addOn->slug],
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
                ->where('booking.summary.currency', 'IDR'));
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
