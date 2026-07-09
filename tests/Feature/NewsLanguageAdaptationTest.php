<?php

namespace Tests\Feature;

use App\Models\ArticleCategory;
use App\Models\Destination;
use App\Models\NewsArticle;
use App\Support\InertiaPublicData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NewsLanguageAdaptationTest extends TestCase
{
    use RefreshDatabase;

    public function test_news_listing_and_detail_payloads_include_localized_article_content(): void
    {
        $this->seed();

        $article = NewsArticle::where('slug', 'paket-wisata-bromo-dari-malang')->firstOrFail();

        $this->get('/news?lang=cn')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('NewsPage')
                ->where('language', 'cn')
                ->where('featured.title.cn', $article->title['cn'])
                ->where('featured.excerpt.cn', $article->excerpt['cn'])
                ->where('featured.tags.2.cn', json_decode('"\\u65e5\\u51fa"', true))
                ->where('featured.readingTime.cn', $article->reading_time['cn'])
                ->where('seo.title', json_decode('"\\u65c5\\u6e38\\u653b\\u7565\\u4e0e\\u52a8\\u6001 | Tinggal Jalan"', true)));

        $this->get("/news/{$article->slug}?lang=cn")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('NewsDetailPage')
                ->where('language', 'cn')
                ->where('article.title.cn', $article->title['cn'])
                ->where('article.sections.0.heading.cn', $article->sections[0]['heading']['cn'])
                ->where('article.sections.0.body.cn', $article->sections[0]['body']['cn'])
                ->where('seo.json_ld.0.headline', $article->title['cn']));
    }

    public function test_missing_translations_fall_back_to_english_for_public_payloads(): void
    {
        [$category, $destination] = $this->createNewsTaxonomy();

        $article = NewsArticle::factory()->create([
            'slug' => 'english-only-guide',
            'article_category_id' => $category->id,
            'destination_id' => $destination->id,
            'title' => ['us' => 'English Only Guide', 'id' => '', 'cn' => ''],
            'excerpt' => ['us' => 'English fallback excerpt.', 'id' => '', 'cn' => ''],
            'cover_alt' => ['us' => 'English alt', 'id' => '', 'cn' => ''],
            'reading_time' => ['us' => '4 min read', 'id' => '', 'cn' => ''],
            'sections' => [[
                'heading' => ['us' => 'English section', 'id' => '', 'cn' => ''],
                'body' => ['us' => 'English body copy.', 'id' => '', 'cn' => ''],
            ]],
        ]);

        $this->get("/news/{$article->slug}?lang=id")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('article.title.id', 'English Only Guide')
                ->where('article.excerpt.id', 'English fallback excerpt.')
                ->where('article.sections.0.heading.id', 'English section')
                ->where('article.sections.0.body.id', 'English body copy.'));
    }

    public function test_legacy_string_tags_and_old_section_shape_are_serialized_safely(): void
    {
        [$category, $destination] = $this->createNewsTaxonomy();

        $article = NewsArticle::factory()->create([
            'article_category_id' => $category->id,
            'destination_id' => $destination->id,
            'tags' => ['Classic', 'Bromo'],
            'sections' => [[
                'type' => 'prose',
                'content' => [
                    'us' => '<p>Legacy English body.</p>',
                    'id' => '<p>Konten lama.</p>',
                ],
            ]],
        ]);

        $payload = InertiaPublicData::article($article->fresh(['articleCategory', 'destination', 'tourPackages']));

        $this->assertSame('Classic', $payload['tags'][0]['us']);
        $this->assertSame('Classic', $payload['tags'][0]['id']);
        $this->assertSame('Classic', $payload['tags'][0]['cn']);
        $this->assertSame('Section 1', $payload['sections'][0]['heading']['us']);
        $this->assertSame('Legacy English body.', $payload['sections'][0]['body']['us']);
        $this->assertSame('Konten lama.', $payload['sections'][0]['body']['id']);
    }

    public function test_news_search_matches_localized_article_content(): void
    {
        $this->seed();

        $term = json_decode('"\\u79c1\\u4eba\\u56e2"', true);

        $this->get('/news?lang=cn&search='.urlencode($term))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('NewsPage')
                ->where('language', 'cn')
                ->where('articles.data.0.slug', 'paket-wisata-bromo-dari-malang'));
    }

    public function test_seeded_news_articles_have_complete_language_payloads(): void
    {
        $data = json_decode(file_get_contents(database_path('seeders/data/prototype.json')), true, flags: JSON_THROW_ON_ERROR);

        foreach ($data['newsArticles'] as $article) {
            foreach (['title', 'excerpt', 'coverAlt', 'readingTime'] as $field) {
                foreach (['us', 'id', 'cn'] as $language) {
                    $this->assertNotEmpty($article[$field][$language] ?? null, "{$article['slug']} missing {$field}.{$language}");
                }
            }

            foreach (['title', 'description'] as $field) {
                foreach (['us', 'id', 'cn'] as $language) {
                    $this->assertNotEmpty($article['seo'][$field][$language] ?? null, "{$article['slug']} missing seo.{$field}.{$language}");
                }
            }

            foreach ($article['sections'] as $index => $section) {
                foreach (['heading', 'body'] as $field) {
                    foreach (['us', 'id', 'cn'] as $language) {
                        $this->assertNotEmpty($section[$field][$language] ?? null, "{$article['slug']} missing sections.{$index}.{$field}.{$language}");
                    }
                }
            }

            foreach ($article['tags'] as $index => $tag) {
                foreach (['us', 'id', 'cn'] as $language) {
                    $this->assertNotEmpty($tag[$language] ?? null, "{$article['slug']} missing tags.{$index}.{$language}");
                }
            }
        }
    }

    /**
     * @return array{ArticleCategory, Destination}
     */
    private function createNewsTaxonomy(): array
    {
        $category = ArticleCategory::create([
            'slug' => 'travel-guide',
            'label' => ['us' => 'Travel Guide', 'id' => 'Panduan', 'cn' => 'Travel Guide'],
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $destination = Destination::create([
            'slug' => 'bromo',
            'name' => 'Bromo',
            'province' => 'East Java',
            'short_description' => ['us' => 'Bromo', 'id' => 'Bromo', 'cn' => 'Bromo'],
            'cover_image' => 'images/destinations/bromo.jpg',
            'sort_order' => 1,
            'is_featured' => true,
            'is_active' => true,
        ]);

        return [$category, $destination];
    }
}
