<?php

namespace App\Filament\Support;

class TourPackageTranslationHelper
{
    public const LOCALIZED_FIELDS = [
        'title',
        'category',
        'tag',
        'excerpt',
        'intro',
        'best_for',
        'difficulty',
        'cover_alt',
        'pickup_label',
        'group_type',
        'review_source',
    ];

    public const LOCALIZED_LIST_FIELDS = [
        'highlights',
        'includes',
        'excludes',
        'notes',
        'details',
        'good_to_know',
        'pickup_areas',
    ];

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    public static function fillMissingFromEnglish(array $state): array
    {
        foreach (self::LOCALIZED_FIELDS as $field) {
            $state[$field] = self::fillLocalizedValue($state[$field] ?? null);
        }

        foreach (self::LOCALIZED_LIST_FIELDS as $field) {
            $state[$field] = self::fillLocalizedList($state[$field] ?? []);
        }

        $state['itineraryItems'] = self::fillItineraryItems($state['itineraryItems'] ?? []);

        return $state;
    }

    /**
     * @return array{id: mixed, us: mixed, cn: mixed}
     */
    public static function fillLocalizedValue(mixed $value): array
    {
        $item = is_array($value) ? $value : [];
        $english = $item['us'] ?? null;

        if (filled($english)) {
            $item['id'] = filled($item['id'] ?? null) ? $item['id'] : $english;
            $item['cn'] = filled($item['cn'] ?? null) ? $item['cn'] : $english;
        }

        return [
            'id' => $item['id'] ?? null,
            'us' => $item['us'] ?? null,
            'cn' => $item['cn'] ?? null,
        ];
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function fillLocalizedList(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        if (isset($items['us']) && is_array($items['us'])) {
            $count = max(count($items['us'] ?? []), count($items['id'] ?? []), count($items['cn'] ?? []));

            return collect(range(0, max(0, $count - 1)))
                ->map(fn (int $index): array => self::fillLocalizedValue([
                    'id' => $items['id'][$index] ?? null,
                    'us' => $items['us'][$index] ?? null,
                    'cn' => $items['cn'][$index] ?? null,
                ]))
                ->all();
        }

        return collect($items)
            ->map(fn (mixed $item): array => self::fillLocalizedValue($item))
            ->all();
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function fillItineraryItems(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->map(function (mixed $item): mixed {
                if (! is_array($item)) {
                    return $item;
                }

                $item['title'] = self::fillLocalizedValue($item['title'] ?? []);
                $item['description'] = self::fillLocalizedValue($item['description'] ?? []);

                return $item;
            })
            ->all();
    }
}
