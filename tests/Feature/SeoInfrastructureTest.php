<?php

namespace Tests\Feature;

use App\Models\NewsArticle;
use App\Models\TourPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SeoInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_has_complete_shared_seo_tags_and_schema(): void
    {
        $this->seed();

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('HomePage')
                ->where('seo.canonical', 'http://localhost:8000/')
                ->where('seo.robots', 'index,follow')
                ->where('seo.twitter_card', 'summary_large_image')
                ->has('seo.json_ld.0')
                ->has('seo.json_ld.1'));
    }

    public function test_route_detail_has_product_metadata_and_schema(): void
    {
        $this->seed();

        $package = TourPackage::where('slug', 'bromo-sunrise')->firstOrFail();

        $this->get("/routes/{$package->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('RouteDetailPage')
                ->where('seo.og_type', 'product')
                ->where('seo.canonical', 'http://localhost:8000/routes/'.$package->slug)
                ->where('seo.json_ld.0.@type', 'Product')
                ->where('seo.json_ld.1.@type', 'TouristTrip'));
    }

    public function test_route_detail_seo_is_generated_from_package_content_and_ignores_overrides(): void
    {
        $this->seed();

        $package = TourPackage::where('slug', 'bromo-sunrise')->firstOrFail();
        $package->update([
            'title' => ['us' => 'Automatic package title.', 'id' => '', 'cn' => ''],
            'excerpt' => ['us' => 'Automatic package excerpt.', 'id' => '', 'cn' => ''],
        ]);

        $this->get("/routes/{$package->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('seo.title', 'Automatic package title. | Tinggal Jalan')
                ->where('seo.description', 'Automatic package excerpt.')
                ->where('seo.og_type', 'product'));
    }

    public function test_route_detail_seo_description_falls_back_to_intro_when_excerpt_is_empty(): void
    {
        $this->seed();

        $package = TourPackage::where('slug', 'bromo-sunrise')->firstOrFail();
        $package->update([
            'title' => ['us' => 'Package with intro fallback.', 'id' => '', 'cn' => ''],
            'excerpt' => ['us' => '', 'id' => '', 'cn' => ''],
            'intro' => ['us' => 'Intro fallback description.', 'id' => '', 'cn' => ''],
        ]);

        $this->get("/routes/{$package->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('seo.title', 'Package with intro fallback. | Tinggal Jalan')
                ->where('seo.description', 'Intro fallback description.'));
    }

    public function test_route_detail_seo_content_falls_back_from_empty_translations_to_english(): void
    {
        $this->seed();

        $package = TourPackage::where('slug', 'bromo-sunrise')->firstOrFail();
        $package->update([
            'title' => ['us' => 'English package title', 'id' => '', 'cn' => ''],
            'excerpt' => ['us' => 'English package excerpt.', 'id' => '', 'cn' => ''],
        ]);

        $this->get("/routes/{$package->slug}?lang=id")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('seo.title', 'English package title | Tinggal Jalan')
                ->where('seo.description', 'English package excerpt.'));
    }

    public function test_news_detail_has_article_metadata_and_schema(): void
    {
        $this->seed();

        $article = NewsArticle::where('slug', 'paket-wisata-bromo-dari-malang')->firstOrFail();

        $this->get("/news/{$article->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('NewsDetailPage')
                ->where('seo.og_type', 'article')
                ->where('seo.canonical', 'http://localhost:8000/news/'.$article->slug)
                ->has('seo.published_time')
                ->has('seo.modified_time')
                ->has('seo.json_ld.0.mainEntityOfPage')
                ->has('seo.json_ld.0.publisher'));
    }

    public function test_search_pages_are_noindex_follow_and_booking_is_noindex_nofollow(): void
    {
        $this->seed();

        $this->get('/routes?search=bromo')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('RoutesPage')
                ->where('seo.robots', 'noindex,follow'));

        $this->get('/news?search=bromo')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('NewsPage')
                ->where('seo.robots', 'noindex,follow'));

        $this->get('/booking')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('BookingPage')
                ->where('seo.robots', 'noindex,nofollow'));
    }

    public function test_sitemap_includes_public_database_pages_and_excludes_private_or_unpublished_pages(): void
    {
        $this->seed();

        $inactive = TourPackage::firstOrFail();
        $inactive->update(['is_active' => false]);
        $draft = NewsArticle::firstOrFail();
        $draft->update(['status' => 'draft']);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<loc>http://localhost:8000/</loc>', false)
            ->assertSee('<loc>http://localhost:8000/routes</loc>', false)
            ->assertSee('<loc>http://localhost:8000/news</loc>', false)
            ->assertSee('<changefreq>weekly</changefreq>', false)
            ->assertDontSee('/admin', false)
            ->assertDontSee('/booking', false)
            ->assertDontSee('/checkout', false)
            ->assertDontSee('/routes/'.$inactive->slug, false)
            ->assertDontSee('/news/'.$draft->slug, false);
    }

    public function test_sitemap_and_route_detail_canonicalize_dirty_route_slugs(): void
    {
        $this->seed();

        $package = TourPackage::where('is_active', true)->firstOrFail();
        $package->update(['slug' => 'dirty-route ']);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('<loc>http://localhost:8000/routes/dirty-route</loc>', false)
            ->assertDontSee('<loc>http://localhost:8000/routes/dirty-route </loc>', false)
            ->assertDontSee('/routes/dirty-route%20', false);

        $this->get('/routes/dirty-route')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('RouteDetailPage')
                ->where('route.slug', 'dirty-route')
                ->where('seo.canonical', 'http://localhost:8000/routes/dirty-route'));

        $this->get('/routes/dirty-route%20')
            ->assertRedirect('/routes/dirty-route');
    }

    public function test_robots_txt_declares_private_paths_and_sitemap(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /admin')
            ->assertSee('Disallow: /booking')
            ->assertSee('Disallow: /checkout/')
            ->assertSee('Sitemap: https://tinggaljalan.com/sitemap.xml');
    }
}
