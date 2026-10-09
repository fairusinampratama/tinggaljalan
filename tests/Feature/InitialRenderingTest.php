<?php

namespace Tests\Feature;

use App\Models\HeroSlide;
use App\Models\SiteSetting;
use App\Support\ServerPageContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InitialRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_initial_locale_contains_complete_shared_copy_and_destination_target(): void
    {
        $this->seed();
        foreach (['us', 'id', 'cn'] as $language) {
            $copy = ServerPageContent::copy($language);
            $this->get('/?lang='.$language)->assertOk()
                ->assertSee('id="destination"', false)
                ->assertInertia(fn (Assert $page) => $page->component('HomePage')
                    ->where('language', $language)
                    ->where('translations.searchTitle', $copy['searchTitle'])
                    ->where('translations.destinationFilterLabel', $copy['destinationFilterLabel'])
                    ->where('translations.exploreTrips', $copy['exploreTrips']));
        }
    }

    public function test_initial_presentation_uses_existing_cms_branding_and_does_not_write_content(): void
    {
        $this->seed();
        $site = SiteSetting::query()->firstOrFail();
        $site->update(['logo_url' => '/storage/admin/branding/custom-logo.png']);
        $hero = HeroSlide::query()->firstOrFail();
        $hero->update(['desktop_image' => 'admin/hero/custom-desktop.jpg', 'mobile_image' => 'admin/hero/custom-mobile.jpg']);
        $before = [$site->fresh()->getAttributes(), HeroSlide::query()->get()->toArray()];
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('src="/storage/admin/branding/custom-logo.png"', $html);
        $this->assertStringContainsString('src="/storage/admin/hero/custom-mobile.jpg"', $html);
        $this->assertStringContainsString('/storage/generated/storage/admin/hero/custom-desktop-1600.webp', $html);
        $this->assertSame($before, [$site->fresh()->getAttributes(), HeroSlide::query()->get()->toArray()]);
        $this->assertSame(1, substr_count($html, '<h1 '));
        $this->assertSame(1, substr_count($html, 'id="destination"'));
        $this->assertStringNotContainsString('clip-path: inset(50%)', $html);
    }

    public function test_all_hero_copy_and_actions_are_present_in_the_native_scrollable_fallback(): void
    {
        $this->seed();
        $html = $this->get('/')->assertOk()->getContent();
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $this->assertSame(2, $xpath->query('//div[contains(@class,"server-native-carousel")]/section//h2')->length);
        $this->assertSame(0, $xpath->query('//div[contains(@class,"server-native-carousel")]//*[@aria-hidden="true"]')->length);
        $this->assertGreaterThan(0, $xpath->query('//section[@id="destination"]//a[contains(@href,"destination=")]')->length);
    }
}
