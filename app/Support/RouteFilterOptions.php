<?php

namespace App\Support;

use App\Models\RouteFilter;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RouteFilterOptions
{
    public const DEFAULT_SLUG = 'recommended';

    /**
     * @var array<int, array{slug: string, label: array{id: string, us: string, cn: string}, sort_order: int}>
     */
    public const DEFAULT_FILTERS = [
        ['slug' => 'recommended', 'label' => ['id' => 'Rekomendasi', 'us' => 'Recommended', 'cn' => '推荐'], 'sort_order' => 0],
        ['slug' => 'family', 'label' => ['id' => 'Keluarga', 'us' => 'Family', 'cn' => '家庭'], 'sort_order' => 10],
        ['slug' => 'adventure', 'label' => ['id' => 'Adventure', 'us' => 'Adventure', 'cn' => '探险'], 'sort_order' => 20],
        ['slug' => 'waterfall', 'label' => ['id' => 'Waterfall', 'us' => 'Waterfall', 'cn' => '瀑布'], 'sort_order' => 30],
        ['slug' => 'sunrise', 'label' => ['id' => 'Sunrise', 'us' => 'Sunrise', 'cn' => '日出'], 'sort_order' => 40],
        ['slug' => 'culture', 'label' => ['id' => 'Budaya', 'us' => 'Culture', 'cn' => '文化'], 'sort_order' => 50],
        ['slug' => 'multi-day', 'label' => ['id' => 'Multi-day', 'us' => 'Multi-day', 'cn' => '多日游'], 'sort_order' => 60],
    ];

    /**
     * @var array<string, array{id: string, us: string, cn: string}>
     */
    private const FALLBACK_LABELS = [
        'recommended' => self::DEFAULT_FILTERS[0]['label'],
        'family' => self::DEFAULT_FILTERS[1]['label'],
        'adventure' => self::DEFAULT_FILTERS[2]['label'],
        'waterfall' => self::DEFAULT_FILTERS[3]['label'],
        'sunrise' => self::DEFAULT_FILTERS[4]['label'],
        'culture' => self::DEFAULT_FILTERS[5]['label'],
        'multi-day' => self::DEFAULT_FILTERS[6]['label'],
    ];

    /**
     * @param  array<int, string>  $selected
     * @return array<string, string>
     */
    public static function adminOptions(array $selected = []): array
    {
        $filters = self::activeFilters();
        $options = $filters->isEmpty()
            ? collect(self::FALLBACK_LABELS)->map(fn (array $label) => $label['us'])->all()
            : $filters
                ->mapWithKeys(fn (RouteFilter $filter) => [$filter->slug => PublicSite::localized($filter->label, 'us')])
                ->all();

        foreach ($selected as $slug) {
            if (blank($slug) || isset($options[$slug])) {
                continue;
            }

            $options[$slug] = self::fallbackText($slug).' (legacy)';
        }

        return $options;
    }

    /**
     * @return array<int, array{value: string, label: array{id: string, us: string, cn: string}}>
     */
    public static function publicOptions(): array
    {
        $filters = self::activeFilters();

        if ($filters->isEmpty()) {
            return collect(array_keys(self::FALLBACK_LABELS))
                ->map(fn (string $slug) => [
                    'value' => $slug,
                    'label' => self::FALLBACK_LABELS[$slug],
                ])
                ->values()
                ->all();
        }

        return $filters
            ->map(fn (RouteFilter $filter) => [
                'value' => $filter->slug,
                'label' => self::localizedArray($filter->label),
            ])
            ->values()
            ->all();
    }

    public static function isActive(string $slug): bool
    {
        if ($slug === self::DEFAULT_SLUG) {
            return true;
        }

        $filters = self::activeFilters();

        if ($filters->isEmpty()) {
            return array_key_exists($slug, self::FALLBACK_LABELS);
        }

        return $filters->contains(fn (RouteFilter $filter) => $filter->slug === $slug);
    }

    public static function seedDefaults(): void
    {
        foreach (self::DEFAULT_FILTERS as $filter) {
            RouteFilter::updateOrCreate(
                ['slug' => $filter['slug']],
                [
                    'label' => $filter['label'],
                    'description' => null,
                    'sort_order' => $filter['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * @return Collection<int, RouteFilter>
     */
    private static function activeFilters(): Collection
    {
        try {
            if (! Schema::hasTable('route_filters')) {
                return collect();
            }

            return RouteFilter::query()
                ->active()
                ->ordered()
                ->get();
        } catch (QueryException) {
            return collect();
        }
    }

    private static function fallbackText(string $slug): string
    {
        return self::FALLBACK_LABELS[$slug]['us'] ?? Str::of($slug)->replace('-', ' ')->title()->toString();
    }

    private static function localizedArray(mixed $value): array|string|null
    {
        if (! is_array($value)) {
            return $value;
        }

        $english = filled($value['us'] ?? null) ? $value['us'] : null;
        $fallback = $english ?? ($value['id'] ?? null) ?? ($value['cn'] ?? null);

        return [
            'id' => filled($value['id'] ?? null) ? $value['id'] : $fallback,
            'us' => $english ?? $fallback,
            'cn' => filled($value['cn'] ?? null) ? $value['cn'] : $fallback,
        ];
    }
}
