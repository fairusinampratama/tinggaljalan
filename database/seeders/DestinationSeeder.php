<?php

namespace Database\Seeders;

use App\Models\Destination;
use Database\Seeders\Concerns\LoadsPrototypeData;
use Illuminate\Database\Seeder;

class DestinationSeeder extends Seeder
{
    use LoadsPrototypeData;

    public function run(): void
    {
        foreach ($this->prototypeData()['destinations'] as $index => $destination) {
            Destination::updateOrCreate(
                ['slug' => $destination['id']],
                [
                    'name' => $destination['name'],
                    'region' => $destination['region'] ?? null,
                    'province' => $destination['province'] ?? null,
                    'short_description' => $this->localized($destination['copy'] ?? null),
                    'cover_image' => $destination['image'] ?? null,
                    'sort_order' => $index + 1,
                    'is_featured' => true,
                    'is_active' => true,
                ],
            );
        }

        Destination::updateOrCreate(
            ['slug' => 'indonesia'],
            [
                'name' => 'Indonesia',
                'region' => 'Indonesia',
                'province' => null,
                'short_description' => $this->localized([
                    'id' => 'Panduan dan rute lintas destinasi Indonesia.',
                    'us' => 'Cross-destination Indonesia guides and routes.',
                    'cn' => '印尼跨目的地旅行指南与路线。',
                ]),
                'cover_image' => '/images/gallery-indonesia-green.jpg',
                'sort_order' => 99,
                'is_featured' => false,
                'is_active' => true,
            ],
        );
    }
}
