<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleAdsConsentTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_include_one_consent_gated_google_ads_bootstrap(): void
    {
        $this->seed();

        foreach (['/', '/booking'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();

            $this->assertSame(1, substr_count($html, 'data-google-ads-consent="AW-18427027980"'));
            $this->assertSame(1, substr_count($html, "})(window, document, 'AW-18427027980');"));
            $this->assertStringContainsString("window.gtag('consent', 'default', consentValues('denied'))", $html);
            $this->assertStringContainsString('window.TinggalJalanConsent', $html);
            $this->assertStringNotContainsString('<script async src="https://www.googletagmanager.com/', $html);
        }
    }

    public function test_google_ads_integration_can_be_disabled(): void
    {
        config()->set('services.google_ads.id', '');

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('data-google-ads-consent=', $html);
        $this->assertStringNotContainsString('window.TinggalJalanConsent', $html);
        $this->assertStringNotContainsString('googletagmanager.com/gtag/js', $html);
    }

    public function test_google_ads_bootstrap_is_not_injected_into_filament(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertDontSee('data-google-ads-consent=', false)
            ->assertDontSee('window.TinggalJalanConsent', false);
    }
}
