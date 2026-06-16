<?php

namespace Database\Seeders;

use App\Models\Setting;
use Database\Seeders\Concerns\LoadsPrototypeData;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    use LoadsPrototypeData;

    public function run(): void
    {
        $brand = $this->prototypeData()['brand'];

        foreach ([
            'whatsapp_number' => $brand['whatsappNumber'] ?? null,
            'logo_url' => $brand['logoUrl'] ?? null,
            'trust_badges' => $brand['trustBadges'] ?? [],
            'contact_details' => $brand['contactDetails'] ?? [],
        ] as $key => $value) {
            Setting::updateOrCreate(
                ['group' => 'site', 'key' => $key],
                ['value' => $value],
            );
        }
    }
}
