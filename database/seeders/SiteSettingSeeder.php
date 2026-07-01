<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Database\Seeders\Concerns\LoadsPrototypeData;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    use LoadsPrototypeData;

    public function run(): void
    {
        $brand = $this->prototypeData()['brand'];

        SiteSetting::updateOrCreate(
            ['id' => 1],
            [
                'whatsapp_number' => $brand['whatsappNumber'] ?? null,
                'logo_url' => $brand['logoUrl'] ?? null,
                'trust_badges' => $brand['trustBadges'] ?? [],
                'contact_email' => $brand['contactDetails']['email'] ?? null,
                'business_address' => $brand['contactDetails']['address'] ?? null,
                'google_maps_url' => $brand['contactDetails']['map_url'] ?? null,
                'service_hours' => $brand['contactDetails']['hours'] ?? [],
            ],
        );
    }
}
