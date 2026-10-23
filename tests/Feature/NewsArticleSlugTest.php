<?php

namespace Tests\Feature;

use App\Filament\Resources\NewsArticles\Pages\CreateNewsArticle;
use App\Filament\Resources\NewsArticles\Pages\EditNewsArticle;
use App\Models\NewsArticle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NewsArticleSlugTest extends TestCase
{
    use RefreshDatabase;

    private function article(): NewsArticle
    {
        $this->seed();
        $this->actingAs(User::where('email', 'admin@tinggaljalan.test')->firstOrFail());

        return NewsArticle::firstOrFail();
    }

    public function test_new_or_changed_slugs_reject_unsafe_values_and_duplicates(): void
    {
        $article = $this->article();
        $original = $article->slug;
        $duplicate = NewsArticle::whereKeyNot($article->id)->firstOrFail()->slug;

        foreach (['Article With Spaces', 'Uppercase', 'bad/segment', 'bad?query', 'bad#fragment', 'already%20encoded', 'bad--slug', 'café', $duplicate, ' '.$duplicate.' '] as $slug) {
            Livewire::test(EditNewsArticle::class, ['record' => $article->id])
                ->fillForm(['slug' => $slug])->call('save')->assertHasFormErrors(['slug']);
            $this->assertSame($original, $article->fresh()->slug);
        }

        Livewire::test(CreateNewsArticle::class)->fillForm([
            'slug' => 'New Article With Spaces',
            'article_category_id' => $article->article_category_id,
            'title' => ['us' => 'A draft title'],
            'excerpt' => ['us' => 'A draft excerpt'],
            'status' => 'draft', 'is_featured' => false,
        ])->call('create')->assertHasFormErrors(['slug']);
        $this->assertDatabaseMissing('news_articles', ['slug' => 'New Article With Spaces']);
    }

    public function test_new_valid_slugs_are_trimmed_and_saved_without_changing_other_articles(): void
    {
        $article = $this->article();
        $before = $article->getAttributes();

        Livewire::test(CreateNewsArticle::class)->fillForm([
            'slug' => ' new-bromo-guide-2026 ',
            'article_category_id' => $article->article_category_id,
            'title' => ['us' => 'A draft title'],
            'excerpt' => ['us' => 'A draft excerpt'],
            'status' => 'draft', 'is_featured' => false,
        ])->call('create')->assertHasNoFormErrors();

        $this->assertDatabaseHas('news_articles', ['slug' => 'new-bromo-guide-2026', 'status' => 'draft']);
        $this->assertSame($before, $article->fresh()->getAttributes());
    }

    public function test_unrelated_edits_preserve_legacy_slugs_content_and_media(): void
    {
        $article = $this->article();

        foreach (['Legacy Article With Spaces', 'Bromo-wildfire', ' Legacy edge spaces '] as $slug) {
            $article->update(['slug' => $slug]);
            $cover = $article->cover_image;
            // The photo feature can persist an absent optional image list as [].
            $sections = array_map(fn (array $section): array => ['images' => [], ...$section], $article->sections);
            Livewire::test(EditNewsArticle::class, ['record' => $article->id])
                ->fillForm(['is_featured' => ! $article->is_featured])
                ->call('save')->assertHasNoFormErrors();

            $article->refresh();
            $this->assertSame($slug, $article->slug);
            $this->assertSame($cover, $article->cover_image);
            $this->assertSame($sections, array_map(fn (array $section): array => ['images' => [], ...$section], $article->sections));
        }
    }
}
