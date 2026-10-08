<?php

namespace Tests\Feature;

use App\Support\ServerPageContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Ssr\Gateway;
use Inertia\Ssr\Response;
use Tests\TestCase;

class PublicHtmlParityTest extends TestCase
{
    use RefreshDatabase;

    private function articlePage(): array
    {
        return ['component' => 'NewsDetailPage', 'props' => [
            'language' => 'us', 'seo' => ['robots' => 'index,follow'],
            'article' => ['title' => ['us' => 'Exact article title'], 'sections' => [
                ['heading' => ['us' => 'First <unsafe> heading?'], 'body' => ['us' => "First paragraph\r\nsecond line\r\n\r\nSecond paragraph <script>alert(1)</script> & text"]],
                ['heading' => ['us' => 'Second section'], 'body' => ['us' => 'Final paragraph']],
            ]],
        ]];
    }

    public function test_article_sections_preserve_headings_order_paragraphs_lines_and_escaped_text(): void
    {
        $html = view('partials.server-page-content', ['page' => $this->articlePage()])->render();
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($document);
        $headings = $xpath->query('//section[@id]/h2');
        $this->assertSame(['First <unsafe> heading?', 'Second section'], array_map(fn ($node) => $node->textContent, iterator_to_array($headings)));
        $paragraphs = $xpath->query('//section[@id][1]/p');
        $this->assertCount(2, $paragraphs);
        $this->assertSame('First paragraphsecond line', $paragraphs[0]->textContent);
        $this->assertSame('Second paragraph <script>alert(1)</script> & text', $paragraphs[1]->textContent);
        $this->assertSame(1, $xpath->query('//section[@id][1]/p[1]/br')->length);
        $this->assertSame(0, $xpath->query('//script')->length);
        $this->assertSame(1, $xpath->query('//h1')->length);
        $this->assertStringNotContainsString('Article Guide', $html);
    }

    public function test_about_visibility_applies_to_every_section_and_does_not_leak_hidden_text_into_intro(): void
    {
        $page = ['component' => 'AboutUsPage', 'props' => ['language' => 'us', 'aboutPage' => [
            'hero' => ['title' => 'About us', 'intro' => 'Visible hero'],
            'sectionVisibility' => array_fill_keys(['story', 'values', 'workflow', 'milestones', 'team', 'profile', 'cta'], false),
            'story' => ['title' => 'Hidden story', 'body' => 'Hidden body'],
            'profile' => ['operating_description' => 'Hidden profile'],
            'cta' => ['title' => 'Hidden CTA'],
        ], 'teamMembers' => [['name' => 'Hidden member']], 'milestones' => [['title' => 'Hidden milestone']]]];
        $html = view('partials.server-page-content', compact('page'))->render();
        $this->assertStringContainsString('Visible hero', $html);
        $this->assertStringNotContainsString('Hidden', $html);
    }

    public function test_route_content_uses_localized_lists_and_includes_commercial_information_and_policies(): void
    {
        $page = ['component' => 'RouteDetailPage', 'props' => ['language' => 'us', 'route' => [
            'title' => 'Tour', 'why' => 'Why choose it', 'basePriceUsd' => 45,
            'packageOptions' => [['title' => 'Selected package', 'description' => 'Package description']],
            'pickupDetails' => ['us' => ['Hotel pickup', 'Airport pickup']],
            'excludes' => [['us' => 'Meals excluded']],
            'policies' => ['cancellation' => [['us' => 'Cancellation condition']], 'confirmation' => [['us' => 'Confirmation condition']]],
        ]]];
        $html = view('partials.server-page-content', compact('page'))->render();
        foreach (['Why choose it', 'Selected package', 'Package description', '$45.00', 'Hotel pickup', 'Airport pickup', 'Meals excluded', 'Cancellation condition', 'Confirmation condition'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertStringContainsString('<h3>Pickup &amp; Drop-off</h3>', $html);
        $this->assertStringContainsString('<ul>', $html);
    }

    public function test_public_language_variants_have_fallback_without_changing_indexing_policy(): void
    {
        $page = $this->articlePage();
        $page['props']['seo']['robots'] = 'noindex,follow';
        $this->assertNotNull(ServerPageContent::fromInertiaPage($page));
        $this->assertSame([], ServerPageContent::hreflang($page));
        $this->assertNull(ServerPageContent::fromInertiaPage(['component' => 'BookingPage']));
    }

    public function test_ssr_success_uses_only_ssr_body_and_unavailable_uses_readable_fallback(): void
    {
        $this->seed();
        $this->mock(Gateway::class)->shouldReceive('dispatch')->andReturn(new Response('', '<div id="app"><h1>SSR body</h1></div>'));
        $this->get('/')->assertOk()->assertSee('SSR body')->assertDontSee('<main class="server-seo-content"', false);
    }

    public function test_unavailable_ssr_does_not_hide_fallback_before_frontend_commit(): void
    {
        $this->seed();
        $this->mock(Gateway::class)->shouldReceive('dispatch')->andReturnNull();
        $this->get('/')->assertOk()->assertSee('<main class="server-seo-content"', false)->assertDontSee("classList.add('js')", false)->assertDontSee('clip-path: inset(50%)', false);
    }

    public function test_unsafe_cms_links_are_not_executable(): void
    {
        $page = ['component' => 'HomePage', 'props' => ['publicData' => ['platformLinks' => [['name' => 'Unsafe', 'url' => 'javascript:alert(1)']]]]];
        $html = view('partials.server-page-content', compact('page'))->render();
        $this->assertStringContainsString('Unsafe', $html);
        $this->assertStringNotContainsString('href="javascript:', $html);
    }
}
