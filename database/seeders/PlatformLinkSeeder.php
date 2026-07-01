<?php

namespace Database\Seeders;

use App\Models\PlatformLink;
use Database\Seeders\Concerns\LoadsPrototypeData;
use Illuminate\Database\Seeder;

class PlatformLinkSeeder extends Seeder
{
    use LoadsPrototypeData;

    public function run(): void
    {
        foreach ($this->prototypeData()['platforms'] as $index => $platform) {
            PlatformLink::updateOrCreate(
                ['name' => $platform['name']],
                [
                    'url' => $platform['url'],
                    'logo' => $platform['logo'] ?? null,
                    'alt' => $platform['alt'] ?? null,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ],
            );
        }
    }
}
