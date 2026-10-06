<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Support\PublicSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreviewContactSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_staging_neutralizes_contact_links_even_when_a_real_number_exists(): void
    {
        $this->app['env'] = 'staging';
        SiteSetting::query()->create(['whatsapp_number' => '628111111111']);
        $this->assertSame('#', PublicSite::whatsappBase());
        $this->assertSame('#', PublicSite::whatsappUrl(['Synthetic preview message']));
        $this->get('/')->assertOk()->assertDontSee('https://wa.me/', false);
    }

    public function test_production_contact_links_keep_the_configured_number(): void
    {
        $this->app['env'] = 'production';
        SiteSetting::query()->create(['whatsapp_number' => '628111111111']);
        $this->assertSame('https://wa.me/628111111111', PublicSite::whatsappBase());
        $this->assertStringStartsWith('https://wa.me/628111111111?text=', PublicSite::whatsappUrl(['Synthetic message']));
    }
}
