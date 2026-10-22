<?php

namespace Tests\Feature;

use App\Models\NewsArticle;
use App\Models\TourPackage;
use App\Support\ArticleUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CatalogAndArticleUrlsTest extends TestCase
{
    use RefreshDatabase;

    public function test_unfiltered_catalog_pages_have_their_own_canonical_and_crawlable_links(): void
    {
        $this->seed();
        $source = TourPackage::query()->active()->firstOrFail();
        for ($index = 0; $index < 13; $index++) {
            $package = $source->replicate();
            $package->slug = 'catalog-page-test-'.$index;
            $package->save();
        }
        NewsArticle::factory()->count(13)->create(['status' => 'published', 'published_at' => now()->subDay()]);

        foreach (['routes', 'news'] as $catalog) {
            foreach (['' => '', '?page=1' => '', '?page=2' => '?page=2', '?page=2&utm_source=test' => '?page=2'] as $query => $canonicalQuery) {
                $canonical = 'http://localhost:8000/'.$catalog.$canonicalQuery;
                $this->get('/'.$catalog.$query)->assertOk()
                    ->assertSee('rel="canonical" href="'.e($canonical).'"', false)
                    ->assertInertia(fn (Assert $page) => $page
                        ->where('seo.canonical', $canonical)
                        ->where('seo.json_ld.0.url', $canonical)
                        ->where('seo.robots', 'index,follow'));
            }

            $this->get('/'.$catalog)->assertSee('page=2', false);
            $this->get('/'.$catalog.'?page=2')->assertSee('page=1', false);
            $this->get('/'.$catalog.'?page=2&lang=id')->assertInertia(fn (Assert $page) => $page
                ->where('seo.canonical', 'http://localhost:8000/'.$catalog.'?page=2')
                ->where('seo.robots', 'noindex,follow'));
            $this->get('/'.$catalog.'?page=2&search=bromo')->assertInertia(fn (Assert $page) => $page
                ->where('seo.canonical', 'http://localhost:8000/'.$catalog)
                ->where('seo.robots', 'noindex,follow'));
        }
    }

    public function test_legacy_article_urls_agree_in_sitemap_metadata_schema_and_initial_links(): void
    {
        $this->seed();
        $article = NewsArticle::query()->firstOrFail();

        foreach (['5 Things to Check Before Bromo', 'Bromo-wildfire', '100%20 Travel & Friends', "Café (Bromo)! 'Guide'"] as $slug) {
            $article->update(['slug' => $slug, 'published_at' => now(), 'is_featured' => true]);
            $path = ArticleUrl::path($slug);
            $canonical = 'http://localhost:8000'.$path;

            $this->get($path)->assertOk()
                ->assertSee('rel="canonical" href="'.e($canonical).'"', false)
                ->assertInertia(fn (Assert $page) => $page
                    ->where('article.slug', $slug)
                    ->where('seo.canonical', $canonical)
                    ->where('seo.json_ld.0.mainEntityOfPage.@id', $canonical)
                    ->where('seo.robots', 'index,follow'));

            $xml = simplexml_load_string($this->get('/sitemap.xml')->assertOk()->getContent());
            $this->assertNotFalse($xml);
            $locations = [];
            foreach ($xml->url as $entry) {
                $locations[] = (string) $entry->loc;
            }
            $this->assertContains($canonical, $locations);
            $this->get('/news')->assertSee('<a href="'.e($path).'"', false)
                ->assertInertia(fn (Assert $page) => $page
                    ->where('seo.json_ld.0.mainEntity.itemListElement', fn ($items) => collect($items)->contains('url', $canonical)));
            $this->assertSame($slug, $article->fresh()->slug);
        }
    }
}
