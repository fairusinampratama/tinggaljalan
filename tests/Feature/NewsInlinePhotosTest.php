<?php

namespace Tests\Feature;

use App\Filament\Resources\NewsArticles\Pages\CreateNewsArticle;
use App\Filament\Resources\NewsArticles\Pages\EditNewsArticle;
use App\Models\NewsArticle;
use App\Models\User;
use App\Support\InertiaPublicData;
use App\Support\ResponsiveImageGenerator;
use App\Support\Seo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use Tests\TestCase;

class NewsInlinePhotosTest extends TestCase
{
    use RefreshDatabase;

    private array $uploadedPaths = [];

    protected function tearDown(): void
    {
        foreach ($this->uploadedPaths as $path) {
            Storage::disk('public')->delete($path);
            foreach (ResponsiveImageGenerator::WIDTHS as $width) {
                Storage::disk('public')->delete(app(ResponsiveImageGenerator::class)->generatedPublicDiskPath($path, $width));
            }
        }
        parent::tearDown();
    }

    private function photo(string $path = 'admin/news/sections/example.jpg'): array
    {
        return ['path' => $path, 'alt' => ['us' => 'Travelers on the mountain trail', 'id' => '', 'cn' => ''],
            'caption' => ['us' => 'Walking with a local guide.', 'id' => 'Berjalan bersama pemandu lokal.', 'cn' => '']];
    }

    private function article(): NewsArticle
    {
        $this->seed();
        $this->actingAs(User::where('email', 'admin@tinggaljalan.test')->firstOrFail());

        return NewsArticle::firstOrFail();
    }

    private function formSections(array $sections): array
    {
        foreach ($sections as &$section) {
            foreach ($section['images'] ?? [] as $index => $image) {
                $section['images'][$index]['path'] = filled($image['path'] ?? null) ? [$image['path']] : [];
            }
        }

        return $sections;
    }

    public function test_create_upload_reopen_reorder_replace_and_remove_references_keep_all_files(): void
    {
        $source = $this->article();
        $sections = [$source->sections[0]];
        $sections[0]['images'] = [
            [...$this->photo(), 'path' => UploadedFile::fake()->image('first.jpg', 1200, 800)],
            [...$this->photo(), 'path' => UploadedFile::fake()->image('second.png', 800, 1200)],
        ];
        Livewire::test(CreateNewsArticle::class)->fillForm([
            'article_category_id' => $source->article_category_id,
            'slug' => 'inline-photo-crud', 'title' => ['us' => 'Inline photo story'],
            'excerpt' => ['us' => 'A test story.'], 'sections' => $this->formSections($sections),
            'status' => 'draft', 'is_featured' => false,
        ])->call('create')->assertHasNoFormErrors();
        $article = NewsArticle::where('slug', 'inline-photo-crud')->firstOrFail();
        $photos = $article->sections[0]['images'];
        $this->assertCount(2, $photos);
        $this->assertIsString($photos[0]['path']);
        $this->assertSame('Walking with a local guide.', $photos[0]['caption']['us']);
        $this->assertSame('Berjalan bersama pemandu lokal.', $photos[0]['caption']['id']);
        $this->uploadedPaths = array_column($photos, 'path');
        foreach ($this->uploadedPaths as $path) {
            Storage::disk('public')->assertExists($path);
            foreach (ResponsiveImageGenerator::WIDTHS as $width) {
                Storage::disk('public')->assertExists(app(ResponsiveImageGenerator::class)->generatedPublicDiskPath($path, $width));
            }
        }
        $this->assertSame(1200, InertiaPublicData::article($article)['sections'][0]['images'][0]['width']);

        // A no-op edit exercises file hydration/dehydration of nested upload fields.
        Livewire::test(EditNewsArticle::class, ['record' => $article->id])->call('save')->assertHasNoFormErrors();
        $this->assertSame($photos, $article->fresh()->sections[0]['images']);
        $sections = $article->fresh()->sections;
        $sections[0]['images'] = array_reverse($photos);
        Livewire::test(EditNewsArticle::class, ['record' => $article->id])->fillForm(['sections' => $this->formSections($sections)])->call('save')->assertHasNoFormErrors();
        $this->assertSame(array_reverse($photos), $article->fresh()->sections[0]['images']);
        $sections[0]['images'][0]['path'] = UploadedFile::fake()->image('replacement.webp', 1000, 750);
        Livewire::test(EditNewsArticle::class, ['record' => $article->id])->fillForm(['sections' => $this->formSections($sections)])->call('save')->assertHasNoFormErrors();
        $replacement = $article->fresh()->sections[0]['images'][0]['path'];
        $this->uploadedPaths[] = $replacement;
        $this->assertNotContains($replacement, array_column($photos, 'path'));
        $sections = $article->fresh()->sections;
        $sections[0]['images'] = [];
        Livewire::test(EditNewsArticle::class, ['record' => $article->id])->fillForm(['sections' => $this->formSections($sections)])->call('save')->assertHasNoFormErrors();
        $this->assertSame([], $article->fresh()->sections[0]['images']);
        $article->delete();
        foreach ($this->uploadedPaths as $path) {
            Storage::disk('public')->assertExists($path);
            Storage::disk('public')->assertExists(app(ResponsiveImageGenerator::class)->generatedPublicDiskPath($path, 768));
        }
    }

    public function test_alt_is_required_only_for_an_actual_photo_and_drafts_remain_supported(): void
    {
        $article = $this->article();
        $article->update(['status' => 'draft']);
        $sections = [$article->sections[0]];
        $sections[0]['images'] = [['path' => null, 'alt' => ['us' => '']]];
        Livewire::test(EditNewsArticle::class, ['record' => $article->id])->fillForm(['sections' => $this->formSections($sections)])->call('save')->assertHasNoFormErrors();
        $this->assertSame([], InertiaPublicData::article($article->fresh())['sections'][0]['images']);
        $sections[0]['images'] = [[...$this->photo(), 'alt' => ['us' => '']]];
        Livewire::test(EditNewsArticle::class, ['record' => $article->id])->fillForm(['sections' => $this->formSections($sections)])->call('save')->assertHasFormErrors();
        $this->assertNull($article->fresh()->sections[0]['images'][0]['path']);
    }

    public function test_payload_filters_invalid_entries_preserves_order_and_localizes_metadata(): void
    {
        $article = $this->article();
        $sections = [$article->sections[0]];
        $sections[0]['images'] = [null, 'broken', ['path' => []], $this->photo(''), $this->photo('javascript:alert(1)'),
            $this->photo('admin/../private.jpg'), $this->photo('//example.org/x.jpg'),
            [...$this->photo(), 'alt' => []], $this->photo(), $this->photo('/images/hero-bromo.jpg')];
        $article->update(['sections' => $sections]);
        $data = InertiaPublicData::article($article->fresh());
        $images = $data['sections'][0]['images'];
        $this->assertSame(['/storage/admin/news/sections/example.jpg', '/images/hero-bromo.jpg'], array_column($images, 'src'));
        $this->assertSame($images[0]['alt']['us'], $images[0]['alt']['id']);
        $this->assertSame($images[0]['alt']['us'], $images[0]['alt']['cn']);
        $this->assertSame('Berjalan bersama pemandu lokal.', $images[0]['caption']['id']);
        $this->assertSame('Walking with a local guide.', $images[0]['caption']['cn']);
        $this->assertArrayNotHasKey('sections', InertiaPublicData::articleCard($article));
        $this->get('/news/'.$article->slug.'?lang=cn')->assertInertia(fn (Assert $page) => $page
            ->has('article.sections.0.images', 2)
            ->where('article.sections.0.images.0.caption.cn', 'Walking with a local guide.')
            ->missing('relatedArticles.0.sections'));
        $this->get('/news')->assertInertia(fn (Assert $page) => $page->missing('articles.data.0.sections')->missing('featured.sections'));
    }

    public function test_initial_html_has_ordered_figures_escaped_captions_and_unchanged_cover_seo(): void
    {
        $article = $this->article();
        $before = $this->get('/news/'.$article->slug)->getContent();
        $sections = [$article->sections[0], $article->sections[1]];
        $sections[0]['images'] = [$this->photo('/images/hero-bromo.jpg'),
            [...$this->photo('/images/destination-tumpak-sewu.jpg'), 'caption' => ['us' => '<script>not executable</script> A waterfall']]];
        $sections[1]['images'] = [[...$this->photo('/images/gallery-indonesia-green.jpg'), 'caption' => []]];
        $article->update(['sections' => $sections]);
        $response = $this->get('/news/'.$article->slug.'?lang=cn')->assertOk();
        $html = $response->getContent();
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $figures = $xpath->query('//main[contains(@class,"server-seo-content")]//figure[contains(@class,"news-section-photo")]');
        $this->assertCount(3, $figures);
        $this->assertSame('/images/hero-bromo.jpg', $xpath->query('.//img', $figures[0])->item(0)->getAttribute('src'));
        $this->assertSame('Travelers on the mountain trail', $xpath->query('.//img', $figures[0])->item(0)->getAttribute('alt'));
        $this->assertSame('lazy', $xpath->query('.//img', $figures[0])->item(0)->getAttribute('loading'));
        $this->assertStringContainsString('480.webp 480w', $xpath->query('.//source', $figures[0])->item(0)->getAttribute('srcset'));
        $this->assertSame('not executable A waterfall', $xpath->query('.//figcaption', $figures[1])->item(0)->textContent);
        $this->assertCount(0, $xpath->query('.//figcaption', $figures[2]));
        $this->assertCount(0, $xpath->query('.//script', $figures[1]));
        // Inline edits advance article modification metadata; social imagery stays the cover.
        $cover = Seo::assetUrl($article->cover_image);
        $response->assertInertia(fn (Assert $page) => $page->where('seo.json_ld.0.image', $cover));
        preg_match('/<meta[^>]*property="og:image" content="([^"]+)"/', $before, $oldCover);
        preg_match('/<meta[^>]*property="og:image" content="([^"]+)"/', $html, $newCover);
        $this->assertSame($oldCover[1], $newCover[1]);
        $response->assertSee('noindex,follow', false);
    }

    public function test_older_articles_and_legacy_sections_still_work_without_photographs(): void
    {
        $article = $this->article();
        $article->update(['sections' => [['content' => ['us' => '<p>Older story text.</p>']]]]);
        $payload = InertiaPublicData::article($article->fresh());
        $this->assertSame('Section 1', $payload['sections'][0]['heading']['us']);
        $this->assertSame('Older story text.', $payload['sections'][0]['body']['us']);
        $this->assertSame([], $payload['sections'][0]['images']);
        $this->get('/news/'.$article->slug)->assertOk()->assertDontSee('class="news-section-photo"', false);
    }
}
