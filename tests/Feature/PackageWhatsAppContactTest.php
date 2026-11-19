<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PackageWhatsAppContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_packages_share_the_configured_site_recipient_and_existing_message(): void
    {
        $this->seed();
        SiteSetting::query()->firstOrFail()->update(['whatsapp_number' => '+62 855 5000 001']);
        $message = 'Halo Tinggal Jalan, saya ingin konsultasi rute perjalanan.';
        $expected = 'https://wa.me/628555000001?text='.rawurlencode($message);

        foreach (['bromo-sunrise', 'jogja-heritage', 'tumpak-sewu-daytrip'] as $slug) {
            $this->get('/routes/'.$slug)->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('RouteDetailPage')
                ->where('publicData.whatsappUrl', $expected)
                ->where('publicData.site.whatsappBaseUrl', 'https://wa.me/628555000001'));
        }
    }

    public function test_missing_site_number_keeps_the_existing_default_recipient(): void
    {
        $this->seed();
        SiteSetting::query()->firstOrFail()->update(['whatsapp_number' => null]);
        $this->get('/routes/bromo-sunrise')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('publicData.whatsappUrl', 'https://wa.me/6281234567890?text='.rawurlencode('Halo Tinggal Jalan, saya ingin konsultasi rute perjalanan.')));
    }
}
