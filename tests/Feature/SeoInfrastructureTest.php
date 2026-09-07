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

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('<html lang="en">', false)
            ->assertSee('<title>Tinggal Jalan | Indonesia Tours &amp; Private Trips</title>', false)
            ->assertSee('<meta data-inertia="description" name="description" content="Plan private Indonesia tours with Tinggal Jalan. Compare Bromo, Tumpak Sewu, Jogja, and Medan trips with clear itineraries and WhatsApp support.">', false)
            ->assertSee('<link rel="icon" href="/favicon.ico" sizes="any">', false)
            ->assertSee('<link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">', false)
            ->assertSee('<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">', false)
            ->assertSee('<meta data-inertia="robots" name="robots" content="index,follow">', false)
            ->assertSee('<link data-inertia="canonical" rel="canonical" href="http://localhost:8000/">', false)
            ->assertSee('<link rel="alternate" hreflang="en" href="http://localhost:8000/">', false)
            ->assertSee('<link rel="alternate" hreflang="x-default" href="http://localhost:8000/">', false)
            ->assertDontSee('hreflang="id"', false)
            ->assertDontSee('hreflang="zh-CN"', false)
            ->assertDontSee('?lang=', false)
            ->assertSee('<meta data-inertia="og:title" property="og:title" content="Tinggal Jalan | Indonesia Tours &amp; Private Trips">', false)
            ->assertSee('<meta data-inertia="twitter:card" name="twitter:card" content="summary_large_image">', false)
            ->assertSee('<script data-inertia="json-ld" type="application/ld+json">', false)
            ->assertInertia(fn (Assert $page) => $page
                ->component('HomePage')
                ->where('seo.canonical', 'http://localhost:8000/')
                ->where('seo.robots', 'index,follow')
                ->where('seo.twitter_card', 'summary_large_image')
                ->has('seo.json_ld.0')
                ->has('seo.json_ld.1'));

        $main = $this->serverMain($response->getContent());

        $this->assertSame(1, substr_count($main, '<h1>'));
        $this->assertGreaterThanOrEqual(250, $this->wordCount($main));
        $this->assertGreaterThanOrEqual(2, substr_count($main, '<h2>'));
        $this->assertSame(0, substr_count($main, '<h3>'));
        $this->assertStringContainsString('<a href="/routes"', $main);
        $this->assertStringContainsString('<a href="/news"', $main);
        $this->assertStringContainsString('<a href="/about-us"', $main);
        $this->assertStringNotContainsString('?destination=', $main);
        $this->assertStringContainsString('<a href="https://wa.me/62811388330"', $main);
        $this->assertStringContainsString('<img', $main);
        $this->assertStringContainsString('alt="', $main);
    }

    public function test_route_detail_has_product_metadata_and_schema(): void
    {
        $this->seed();

        $package = TourPackage::where('slug', 'bromo-sunrise')->firstOrFail();

        $response = $this->get("/routes/{$package->slug}");

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('RouteDetailPage')
                ->where('seo.og_type', 'product')
                ->where('seo.canonical', 'http://localhost:8000/routes/'.$package->slug)
                ->where('seo.json_ld.0.@type', 'Product')
                ->where('seo.json_ld.1.@type', 'TouristTrip'));

        $main = $this->serverMain($response->getContent());

        $this->assertSame(1, substr_count($main, '<h1>'));
        $this->assertStringContainsString(e($package->title['us']), $main);
        $this->assertStringContainsString('Route Highlights', $main);
        $this->assertStringContainsString('Itinerary', $main);
        $this->assertStringContainsString('Related Travel Guides', $main);
        $this->assertStringContainsString('<img', $main);
        $this->assertStringContainsString('alt="', $main);
    }

    public function test_route_detail_product_schema_uses_the_lowest_tier_price(): void
    {
        $this->seed();

        $package = TourPackage::where('slug', 'bromo-sunrise')->firstOrFail();
        $package->update(['pricing_mode' => 'tiered']);
        $package->priceTiers()->delete();
        $package->priceTiers()->createMany([
            ['min_pax' => 1, 'max_pax' => 1, 'price_idr' => 900000, 'price_usd' => 60, 'sort_order' => 1],
            ['min_pax' => 2, 'max_pax' => null, 'price_idr' => 600000, 'price_usd' => 40, 'sort_order' => 2],
        ]);

        $this->get("/routes/{$package->slug}?lang=id")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('route.basePriceIdr', 600000)
                ->where('seo.json_ld.0.offers.priceCurrency', 'IDR')
                ->where('seo.json_ld.0.offers.price', 600000));
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

        $response = $this->get("/news/{$article->slug}");

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('NewsDetailPage')
                ->where('seo.og_type', 'article')
                ->where('seo.canonical', 'http://localhost:8000/news/'.$article->slug)
                ->has('seo.published_time')
                ->has('seo.modified_time')
                ->has('seo.json_ld.0.mainEntityOfPage')
                ->has('seo.json_ld.0.publisher'));

        $main = $this->serverMain($response->getContent());

        $this->assertSame(1, substr_count($main, '<h1>'));
        $this->assertStringContainsString(e($article->title['us']), $main);
        $this->assertStringContainsString('Article Guide', $main);
        $this->assertStringContainsString('Related Tour Routes', $main);
        $this->assertStringContainsString('<img', $main);
        $this->assertStringContainsString('alt="', $main);
    }

    public function test_news_detail_normalizes_and_limits_long_meta_descriptions(): void
    {
        $this->seed();

        $article = NewsArticle::query()->published()->firstOrFail();
        $article->update([
            'excerpt' => [
                'us' => '<p>'.str_repeat('A detailed Mount Bromo travel guide with practical planning advice. ', 8)."</p>\n\n",
                'id' => '',
                'cn' => '',
            ],
            'seo' => [],
        ]);

        $this->get('/news/'.$article->slug)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('seo.description', fn (string $description): bool => mb_strlen($description) <= 160
                    && str_ends_with($description, '...')
                    && ! str_contains($description, '<p>')
                    && ! str_contains($description, "\n")));
    }

    public function test_news_detail_appends_the_brand_exactly_once(): void
    {
        $this->seed();

        $article = NewsArticle::query()->published()->firstOrFail();
        $article->update([
            'seo' => [
                'title' => [
                    'us' => 'Bromo Tour Package from Malang | Tinggal Jalan | Tinggal Jalan',
                    'id' => '',
                    'cn' => '',
                ],
                'description' => $article->excerpt,
            ],
        ]);

        $response = $this->get('/news/'.$article->slug)->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->where('seo.title', 'Bromo Tour Package from Malang | Tinggal Jalan'));
        $this->assertSame(1, substr_count($response->getContent(), '<title>'));
        $this->assertStringContainsString(
            '<title>Bromo Tour Package from Malang | Tinggal Jalan</title>',
            html_entity_decode($response->getContent()),
        );
    }

    public function test_public_indexes_render_server_visible_links_to_detail_pages(): void
    {
        $this->seed();

        $route = TourPackage::query()->active()->firstOrFail();
        $article = NewsArticle::query()->published()->firstOrFail();

        $routesMain = $this->serverMain($this->get('/routes')->assertOk()->getContent());
        $newsMain = $this->serverMain($this->get('/news')->assertOk()->getContent());

        $this->assertSame(1, substr_count($routesMain, '<h1>'));
        $this->assertStringContainsString('/routes/'.trim((string) $route->slug), $routesMain);
        $this->assertStringContainsString('Available Tour Packages', $routesMain);
        $this->assertSame(1, substr_count($newsMain, '<h1>'));
        $this->assertStringContainsString('/news/'.trim((string) $article->slug), $newsMain);
        $this->assertStringContainsString('Latest Travel Articles', $newsMain);
    }

    public function test_about_page_renders_server_visible_company_content(): void
    {
        $this->seed();

        $main = $this->serverMain($this->get('/about-us')->assertOk()->getContent());

        $this->assertSame(1, substr_count($main, '<h1>'));
        $this->assertStringContainsString('Tinggal Jalan', $main);
        $this->assertStringContainsString('Tinggal Jalan Team', $main);
        $this->assertStringContainsString('<img', $main);
        $this->assertStringContainsString('<a href="/routes"', $main);
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
            ->assertDontSee('<main class="server-seo-content"', false)
            ->assertInertia(fn (Assert $page) => $page
                ->component('BookingPage')
                ->where('seo.robots', 'noindex,nofollow'));
    }

    public function test_legacy_language_query_variants_are_not_index_targets(): void
    {
        $this->seed();

        $package = TourPackage::query()->active()->firstOrFail();
        $article = NewsArticle::query()->published()->firstOrFail();
        $paths = [
            '/?lang=id' => 'http://localhost:8000/',
            '/routes/'.$package->slug.'?lang=cn' => 'http://localhost:8000/routes/'.$package->slug,
            '/news/'.$article->slug.'?lang=id' => 'http://localhost:8000/news/'.$article->slug,
        ];

        foreach ($paths as $path => $canonical) {
            $response = $this->get($path)->assertOk();

            $response
                ->assertDontSee('<main class="server-seo-content"', false)
                ->assertDontSee('hreflang="id"', false)
                ->assertDontSee('hreflang="zh-CN"', false)
                ->assertInertia(fn (Assert $page) => $page
                    ->where('seo.robots', 'noindex,follow')
                    ->where('seo.canonical', $canonical));
        }
    }

    public function test_publishing_an_article_makes_it_discoverable_and_future_articles_stay_hidden(): void
    {
        $this->seed();

        $draft = NewsArticle::factory()->create([
            'slug' => 'automatic-indexing-test',
            'title' => ['us' => 'Automatic Indexing Test', 'id' => '', 'cn' => ''],
            'status' => 'draft',
            'published_at' => null,
        ]);
        $future = NewsArticle::factory()->create([
            'slug' => 'future-indexing-test',
            'status' => 'published',
            'published_at' => now()->addDay(),
        ]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertDontSee('/news/'.$draft->slug, false)
            ->assertDontSee('/news/'.$future->slug, false);

        $draft->update(['status' => 'published']);
        $draft->refresh();

        $this->assertNotNull($draft->published_at);
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('<loc>http://localhost:8000/news/'.$draft->slug.'</loc>', false)
            ->assertSee('<lastmod>'.$draft->updated_at->toAtomString().'</lastmod>', false)
            ->assertDontSee('/news/'.$future->slug, false);

        $newsMain = $this->serverMain($this->get('/news')->assertOk()->getContent());
        $this->assertStringContainsString('<a href="/news/'.$draft->slug.'"', $newsMain);
        $this->get('/news/'.$draft->slug)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('seo.robots', 'index,follow')
                ->where('seo.canonical', 'http://localhost:8000/news/'.$draft->slug));
        $this->get('/news/'.$future->slug)->assertRedirect('/news');
    }

    public function test_sitemap_includes_public_database_pages_and_excludes_private_or_unpublished_pages(): void
    {
        $this->seed();

        $inactive = TourPackage::firstOrFail();
        $inactive->update(['is_active' => false]);
        $draft = NewsArticle::firstOrFail();
        $draft->update(['status' => 'draft']);

        $response = $this->get('/sitemap.xml');

        $response
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

        $this->assertFalse($response->headers->has('Set-Cookie'));
        $this->assertStringContainsString('public', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('max-age=3600', (string) $response->headers->get('Cache-Control'));
        $this->assertStringNotContainsString('private', (string) $response->headers->get('Cache-Control'));

        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);
        $locations = [];
        foreach ($xml->url as $entry) {
            $locations[] = (string) $entry->loc;
        }
        $this->assertSame($locations, array_values(array_unique($locations)));

        foreach ($xml->url as $entry) {
            $lastModified = trim((string) $entry->lastmod);
            $this->assertTrue($lastModified === '' || strtotime($lastModified) !== false);
        }
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
        $robots = file_get_contents(public_path('robots.txt'));

        $this->assertIsString($robots);
        $this->assertStringContainsString("Allow: /\n", $robots);
        $this->assertStringContainsString("Disallow: /admin\n", $robots);
        $this->assertStringContainsString("Disallow: /booking\n", $robots);
        $this->assertStringContainsString("Disallow: /checkout/\n", $robots);
        $this->assertStringContainsString('Sitemap: https://tinggaljalan.com/sitemap.xml', $robots);
    }

    public function test_favicon_assets_are_valid_square_images(): void
    {
        foreach ([
            'favicon-96x96.png' => 96,
            'favicon.png' => 512,
            'apple-touch-icon.png' => 180,
        ] as $filename => $expectedSize) {
            $image = getimagesize(public_path($filename));

            $this->assertIsArray($image, $filename.' must be a valid image.');
            $this->assertSame($expectedSize, $image[0]);
            $this->assertSame($expectedSize, $image[1]);
            $this->assertSame('image/png', $image['mime']);
        }

        $ico = file_get_contents(public_path('favicon.ico'));
        $this->assertIsString($ico);
        $this->assertGreaterThan(100, strlen($ico));
        $this->assertSame("\x00\x00\x01\x00", substr($ico, 0, 4));
    }

    public function test_www_host_redirects_to_canonical_non_www_url(): void
    {
        $this->get('https://www.tinggaljalan.com/routes/BROMO?lang=id')
            ->assertRedirect('https://tinggaljalan.com/routes/BROMO?lang=id')
            ->assertStatus(301);
    }

    private function serverMain(string $html): string
    {
        preg_match('/<main class="server-seo-content".*?<\/main>/s', $html, $matches);

        $this->assertNotEmpty($matches[0] ?? null, 'Expected server-rendered SEO fallback content.');

        return $matches[0];
    }

    private function wordCount(string $html): int
    {
        return str_word_count(strip_tags($html));
    }
}
