<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Throwable;

class ResponsiveImageGenerator
{
    public const WIDTHS = [480, 768, 960, 1200, 1600];

    public const QUALITY = 80;

    public function generateForPublicDiskPath(?string $path, bool $missingOnly = false): int
    {
        if (! $this->isSupported()) {
            Log::warning('Responsive image generation skipped because GD WebP support is unavailable.');

            return 0;
        }

        $relative = $this->normalizePublicDiskPath($path);

        if ($relative === null || str_starts_with($relative, 'generated/')) {
            return 0;
        }

        $source = storage_path('app/public/'.$relative);

        if (! is_file($source) || ! $this->isSupportedImage($source)) {
            return 0;
        }

        try {
            [$sourceWidth, $sourceHeight, $type] = getimagesize($source) ?: [null, null, null];

            if (! $sourceWidth || ! $sourceHeight || ! $type) {
                return 0;
            }

            $image = $this->createImage($source, $type);

            if (! $image) {
                return 0;
            }

            try {
                return $this->writeVariants($image, $relative, $sourceWidth, $sourceHeight, $missingOnly);
            } finally {
                imagedestroy($image);
            }
        } catch (Throwable $exception) {
            Log::warning('Responsive image generation failed.', [
                'path' => $relative,
                'message' => $exception->getMessage(),
            ]);

            return 0;
        }
    }

    public function isSupported(): bool
    {
        return extension_loaded('gd')
            && function_exists('imagewebp')
            && function_exists('imagecreatetruecolor');
    }

    public function generatedPublicDiskPath(string $relative, int $width): string
    {
        $relative = $this->normalizePublicDiskPath($relative) ?? $relative;
        $withoutExtension = preg_replace('/\.[a-z0-9]+$/i', '', $relative) ?: $relative;

        return "generated/storage/{$withoutExtension}-{$width}.webp";
    }

    private function writeVariants($image, string $relative, int $sourceWidth, int $sourceHeight, bool $missingOnly): int
    {
        $generated = 0;

        foreach (self::WIDTHS as $width) {
            $output = storage_path('app/public/'.$this->generatedPublicDiskPath($relative, $width));

            if ($missingOnly && is_file($output)) {
                continue;
            }

            File::ensureDirectoryExists(dirname($output), 0755, true);

            $targetWidth = min($width, $sourceWidth);
            $targetHeight = max(1, (int) round($sourceHeight * ($targetWidth / $sourceWidth)));
            $variant = imagecreatetruecolor($targetWidth, $targetHeight);

            if (! $variant) {
                continue;
            }

            imagealphablending($variant, false);
            imagesavealpha($variant, true);

            try {
                imagecopyresampled(
                    $variant,
                    $image,
                    0,
                    0,
                    0,
                    0,
                    $targetWidth,
                    $targetHeight,
                    $sourceWidth,
                    $sourceHeight,
                );

                if (imagewebp($variant, $output, self::QUALITY)) {
                    $generated++;
                }
            } finally {
                imagedestroy($variant);
            }
        }

        return $generated;
    }

    private function createImage(string $source, int $type)
    {
        return match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($source),
            IMAGETYPE_PNG => imagecreatefrompng($source),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($source) : false,
            default => false,
        };
    }

    private function isSupportedImage(string $source): bool
    {
        return in_array(strtolower(pathinfo($source, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true);
    }

    private function normalizePublicDiskPath(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        $path = strtok((string) $path, '?') ?: (string) $path;
        $path = ltrim($path, '/');

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        if (str_starts_with($path, 'public/')) {
            $path = substr($path, strlen('public/'));
        }

        return $path === '' ? null : $path;
    }
}