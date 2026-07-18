<?php

namespace Tests\Feature;

use App\Filament\Resources\NewsArticles\NewsArticleResource;
use App\Filament\Resources\NewsArticles\Pages\EditNewsArticle;
use App\Filament\Support\NewsArticleReadiness;
use App\Models\NewsArticle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NewsArticleReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_completeness_is_independent_from_publication_status(): void
    {
        $this->seed();

        $article = NewsArticle::query()->firstOrFail();

        $this->assertSame('Complete', NewsArticleReadiness::status($article));
        $this->assertSame("\u{2014}", NewsArticleReadiness::summary($article));

        $article->update(['status' => 'draft']);

        $this->assertSame('Complete', NewsArticleReadiness::status($article->fresh()));

        $this->assertSame([
            'Category',
            'English title',
            'Slug',
            'Cover image',
            'English cover alt text',
            'English excerpt',
            'Content sections',
        ], NewsArticleReadiness::missingItemsFromState([
            'article_category_id' => null,
            'title' => ['us' => ''],
            'slug' => '',
            'cover_image' => null,
            'cover_alt' => ['us' => ''],
            'excerpt' => ['us' => ''],
            'sections' => [],
        ]));
    }

    public function test_content_sections_require_an_english_heading_and_body(): void
    {
        $this->seed();

        $article = NewsArticle::query()->firstOrFail();
        $state = $this->completeState($article);

        $state['sections'] = [[
            'heading' => ['us' => 'A useful heading'],
            'body' => ['us' => ''],
        ]];
        $this->assertContains('Content sections', NewsArticleReadiness::missingItemsFromState($state));

        $state['sections'] = [[
            'heading' => ['us' => ''],
            'body' => ['us' => 'Useful article content'],
        ]];
        $this->assertContains('Content sections', NewsArticleReadiness::missingItemsFromState($state));

        $state['sections'] = [[
            'heading' => ['us' => 'A useful heading'],
            'body' => ['us' => 'Useful article content'],
        ]];
        $this->assertNotContains('Content sections', NewsArticleReadiness::missingItemsFromState($state));
    }

    public function test_incomplete_filter_and_badge_only_flag_published_incomplete_articles(): void
    {
        $this->seed();

        $article = NewsArticle::query()->firstOrFail();
        $article->update(['cover_alt' => null, 'status' => 'published']);

        $this->assertTrue(NewsArticleReadiness::applyIncomplete(NewsArticle::query())
            ->whereKey($article)
            ->exists());
        $this->assertSame('1', NewsArticleResource::getNavigationBadge());

        $this->get(route('news.show', $article->slug))->assertOk();

        $article->update(['status' => 'draft']);

        $this->assertNull(NewsArticleResource::getNavigationBadge());
    }

    public function test_admin_must_complete_or_unpublish_an_incomplete_article(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tinggaljalan.test')->firstOrFail();
        $article = NewsArticle::query()->firstOrFail();
        $article->update(['cover_alt' => null, 'status' => 'published']);

        $this->actingAs($admin);
        Livewire::test(EditNewsArticle::class, ['record' => $article->getRouteKey()])
            ->set('data.status', 'published')
            ->call('save')
            ->assertHasFormErrors(['status']);

        $this->assertSame('published', $article->fresh()->status);

        Livewire::test(EditNewsArticle::class, ['record' => $article->getRouteKey()])
            ->set('data.status', 'draft')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('draft', $article->fresh()->status);
    }

    public function test_admin_can_publish_a_complete_draft(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tinggaljalan.test')->firstOrFail();
        $article = NewsArticle::query()->firstOrFail();
        $article->update(['status' => 'draft']);

        $this->actingAs($admin);
        Livewire::test(EditNewsArticle::class, ['record' => $article->getRouteKey()])
            ->set('data.status', 'published')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('published', $article->fresh()->status);
    }

    /** @return array<string, mixed> */
    private function completeState(NewsArticle $article): array
    {
        return [
            'article_category_id' => $article->article_category_id,
            'title' => $article->title,
            'slug' => $article->slug,
            'cover_image' => $article->cover_image,
            'cover_alt' => $article->cover_alt,
            'excerpt' => $article->excerpt,
            'sections' => $article->sections,
        ];
    }
}
