<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Support\InertiaPublicData;
use App\Support\PublicSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreviewContactSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_staging_neutralizes_contact_links_even_when_a_real_number_exists(): void
    {
        $this->app['env'] = 'staging';
        SiteSetting::query()->create(['whatsapp_number' => '628111111111', 'contact_email' => 'public@example.org', 'business_address' => 'Public business office', 'google_maps_url' => 'https://maps.example.org/public-office']);
        $contact = InertiaPublicData::site()['contactDetails'];
        $this->assertSame('public@example.org', $contact['email']);
        $this->assertSame('Public business office', $contact['address']);
        $this->assertSame('https://maps.example.org/public-office', $contact['map_url']);
        $this->assertSame('628111111111', $contact['whatsapp']);
        $this->assertFalse($contact['actionsEnabled']);
        foreach (['email_url', 'emailHref', 'whatsapp_url'] as $field) {
            $this->assertSame('#', $contact[$field]);
        }
        $this->assertSame('#', PublicSite::whatsappBase());
        $this->assertSame('#', PublicSite::whatsappUrl(['Synthetic preview message']));
        $this->get('/')->assertOk()->assertDontSee('https://wa.me/', false);
    }

    public function test_production_contact_links_keep_the_configured_number(): void
    {
        $this->app['env'] = 'production';
        SiteSetting::query()->create(['whatsapp_number' => '628111111111', 'contact_email' => 'public@example.org']);
        $contact = InertiaPublicData::site()['contactDetails'];
        $this->assertTrue($contact['actionsEnabled']);
        $this->assertSame('mailto:public@example.org', $contact['email_url']);
        $this->assertSame('tel:+628111111111', $contact['whatsapp_url']);
        $this->assertSame('https://wa.me/628111111111', PublicSite::whatsappBase());
        $this->assertStringStartsWith('https://wa.me/628111111111?text=', PublicSite::whatsappUrl(['Synthetic message']));
    }
}
