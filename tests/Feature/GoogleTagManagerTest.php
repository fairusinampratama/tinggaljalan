<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleTagManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.google_tag_manager.enabled', true);
        config()->set('services.google_tag_manager.id', 'GTM-58KNQXXZ');
        config()->set('services.google_ads.consent_enabled', false);
    }

    public function test_gtm_works_without_direct_ads_and_does_not_bypass_consent(): void
    {
        $this->seed();

        foreach (['/', '/news', '/routes', '/booking'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();

            $this->assertSame(1, substr_count($html, 'data-google-tag-manager="GTM-58KNQXXZ"'));
            $this->assertStringContainsString("window.gtag('consent', 'default', consentValues('denied'))", $html);
            $this->assertStringNotContainsString('googletagmanager.com/ns.html', $html);
            $this->assertStringNotContainsString('<script src="https://www.googletagmanager.com', $html);
        }
    }

    public function test_disabled_or_invalid_container_does_not_enable_tracking(): void
    {
        foreach (['', 'invalid', 'GTM-X"<script>'] as $id) {
            config()->set('services.google_tag_manager.id', $id);
            $this->get('/')->assertOk()->assertDontSee('window.TinggalJalanConsent', false);
        }

        config()->set('services.google_tag_manager.id', 'GTM-58KNQXXZ');
        config()->set('services.google_tag_manager.enabled', false);
        $this->get('/')->assertOk()->assertDontSee('window.TinggalJalanConsent', false);
    }

    public function test_gtm_is_not_injected_into_admin(): void
    {
        $this->get('/admin/login')->assertOk()
            ->assertDontSee('data-google-tag-manager=', false)
            ->assertDontSee('window.TinggalJalanConsent', false);
    }
}
