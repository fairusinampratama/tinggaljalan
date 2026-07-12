<?php

namespace App\Support;

use App\Models\HeroSlide;

class ResponsiveImage
{
    public const WIDTHS = [480, 768, 960, 1200, 1600];

    public static function generatedPath(string $path, int $width): string
    {
        $normalized = self::normalizePath($path);
        $extension = pathinfo($normalized, PATHINFO_EXTENSION);
        $withoutExtension = $extension
            ? substr($normalized, 0, -strlen($extension) - 1)
            : $normalized;

        if (str_starts_with($normalized, '/storage/')) {
            return '/storage/generated/'.ltrim($withoutExtension, '/')."-{$width}.webp";
        }

        return '/images/generated/'.ltrim($withoutExtension, '/')."-{$width}.webp";
    }

    public static function srcSet(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        return collect(self::WIDTHS)
            ->map(fn (int $width): string => self::generatedPath($path, $width)." {$width}w")
            ->implode(', ');
    }

    public static function normalizePath(?string $path): string
    {
        $asset = PublicSite::assetPath($path);

        return str_starts_with($asset, '/') ? $asset : '/'.$asset;
    }

    public static function heroPreloads(): array
    {
        $slide = HeroSlide::query()->activeScheduled()->ordered()->first();
        $desktop = self::normalizePath($slide?->desktop_image ?: '/images/hero-bromo.jpg');
        $mobile = self::normalizePath($slide?->mobile_image ?: $slide?->desktop_image ?: '/images/hero-bromo.jpg');

        return [
            [
                'href' => self::generatedPath($mobile, 960),
                'srcset' => self::srcSet($mobile),
                'imagesizes' => '100vw',
                'media' => '(max-width: 639px)',
            ],
            [
                'href' => self::generatedPath($desktop, 1600),
                'srcset' => self::srcSet($desktop),
                'imagesizes' => '100vw',
                'media' => '(min-width: 640px)',
            ],
        ];
    }
}
