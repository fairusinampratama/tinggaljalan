<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteDetails;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SiteDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_and_save_site_details()
    {
        $admin = User::factory()->create(['email' => 'admin_test_123@tinggaljalan.test']);
        
        $this->actingAs($admin);

        Livewire::test(SiteDetails::class)
            ->assertOk()
            ->set('data.whatsapp_number', '+6281234567890')
            ->set('data.contact_email', 'hello@example.com')
            ->set('data.business_address', '123 Fake Street')
            ->set('data.google_maps_url', 'https://maps.google.com/?q=123')
            ->set('data.service_hours', [
                'id' => 'Senin - Jumat',
                'us' => 'Mon - Fri',
                'cn' => '周一 - 周五',
            ])
            ->set('data.service_areas', ['Malang', 'Bromo'])
            ->set('data.trust_badges', ['Safe', 'Verified'])
            ->call('save')
            ->assertHasNoFormErrors();

        $setting = SiteSetting::first();
        $this->assertEquals('+6281234567890', $setting->whatsapp_number);
        $this->assertEquals('hello@example.com', $setting->contact_email);
        $this->assertEquals('123 Fake Street', $setting->business_address);
        $this->assertEquals('https://maps.google.com/?q=123', $setting->google_maps_url);
        $this->assertEquals('Senin - Jumat', $setting->service_hours['id']);
        $this->assertEquals(['Malang', 'Bromo'], $setting->service_areas);
        $this->assertEquals(['Safe', 'Verified'], $setting->trust_badges);
    }
}
