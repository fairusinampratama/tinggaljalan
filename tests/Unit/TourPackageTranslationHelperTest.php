<?php

namespace Tests\Unit;

use App\Filament\Support\TourPackageTranslationHelper;
use PHPUnit\Framework\TestCase;

class TourPackageTranslationHelperTest extends TestCase
{
    public function test_it_fills_missing_simple_translations_from_english(): void
    {
        $state = TourPackageTranslationHelper::fillMissingFromEnglish([
            'title' => [
                'us' => 'Bromo Sunrise Private Trip',
                'id' => '',
                'cn' => null,
            ],
        ]);

        $this->assertSame('Bromo Sunrise Private Trip', $state['title']['id']);
        $this->assertSame('Bromo Sunrise Private Trip', $state['title']['cn']);
    }

    public function test_it_does_not_overwrite_existing_translations(): void
    {
        $state = TourPackageTranslationHelper::fillMissingFromEnglish([
            'title' => [
                'us' => 'Bromo Sunrise Private Trip',
                'id' => 'Paket Sunrise Bromo',
                'cn' => '中文现有内容',
            ],
        ]);

        $this->assertSame('Paket Sunrise Bromo', $state['title']['id']);
        $this->assertSame('中文现有内容', $state['title']['cn']);
    }

    public function test_it_fills_missing_localized_repeater_items(): void
    {
        $state = TourPackageTranslationHelper::fillMissingFromEnglish([
            'highlights' => [
                [
                    'us' => 'Private jeep for sunrise',
                    'id' => '',
                    'cn' => '',
                ],
                [
                    'us' => 'Flexible pickup',
                    'id' => 'Pickup fleksibel',
                    'cn' => '',
                ],
            ],
        ]);

        $this->assertSame('Private jeep for sunrise', $state['highlights'][0]['id']);
        $this->assertSame('Private jeep for sunrise', $state['highlights'][0]['cn']);
        $this->assertSame('Pickup fleksibel', $state['highlights'][1]['id']);
        $this->assertSame('Flexible pickup', $state['highlights'][1]['cn']);
    }

    public function test_it_fills_missing_itinerary_translations(): void
    {
        $state = TourPackageTranslationHelper::fillMissingFromEnglish([
            'itineraryItems' => [
                'item-1' => [
                    'title' => [
                        'us' => 'Sunrise viewpoint',
                        'id' => '',
                        'cn' => '',
                    ],
                    'description' => [
                        'us' => 'Arrive before sunrise.',
                        'id' => 'Tiba sebelum matahari terbit.',
                        'cn' => '',
                    ],
                ],
            ],
        ]);

        $this->assertSame('Sunrise viewpoint', $state['itineraryItems']['item-1']['title']['id']);
        $this->assertSame('Sunrise viewpoint', $state['itineraryItems']['item-1']['title']['cn']);
        $this->assertSame('Tiba sebelum matahari terbit.', $state['itineraryItems']['item-1']['description']['id']);
        $this->assertSame('Arrive before sunrise.', $state['itineraryItems']['item-1']['description']['cn']);
    }
}
