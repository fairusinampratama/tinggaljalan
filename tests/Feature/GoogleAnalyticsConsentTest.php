<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleAnalyticsConsentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.google_ads.consent_enabled', false);
        config()->set('services.google_tag_manager.enabled', false);
        config()->set('services.google_analytics.enabled', true);
        config()->set('services.google_analytics.id', 'G-1L9N8XTME6');
    }

    public function test_analytics_works_independently_without_an_unconditional_script(): void
    {
        $this->seed();

        foreach (['/', '/news', '/routes', '/booking'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();

            $this->assertSame(1, substr_count($html, 'data-google-analytics="G-1L9N8XTME6"'));
            $this->assertStringContainsString("window.gtag('consent', 'default', consentValues('denied'))", $html);
            $this->assertStringNotContainsString('<script async src="https://www.googletagmanager.com/', $html);
        }
    }

    public function test_disabled_or_invalid_analytics_id_does_not_enable_tracking(): void
    {
        foreach (['', 'invalid', 'G-X"<script>'] as $id) {
            config()->set('services.google_analytics.id', $id);
            $this->get('/')->assertOk()->assertDontSee('window.TinggalJalanConsent', false);
        }

        config()->set('services.google_analytics.id', 'G-1L9N8XTME6');
        config()->set('services.google_analytics.enabled', false);
        $this->get('/')->assertOk()->assertDontSee('window.TinggalJalanConsent', false);
    }

    public function test_analytics_is_not_injected_into_admin(): void
    {
        $this->get('/admin/login')->assertOk()->assertDontSee('G-1L9N8XTME6', false);
    }
}
