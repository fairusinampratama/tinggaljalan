<?php

namespace App\Console\Commands;

use App\Support\ResponsiveImageGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateResponsiveImages extends Command
{
    protected $signature = 'images:generate-responsive
        {--missing : Only generate variants that do not already exist}';

    protected $description = 'Generate responsive WebP variants for uploaded public storage images';

    public function handle(ResponsiveImageGenerator $images): int
    {
        if (! $images->isSupported()) {
            $this->error('GD with WebP support is required to generate responsive images.');

            return self::FAILURE;
        }

        $root = storage_path('app/public');

        if (! is_dir($root)) {
            $this->warn('Public storage does not exist yet.');

            return self::SUCCESS;
        }

        $sources = collect(File::allFiles($root))
            ->reject(fn ($file): bool => str_contains($file->getPathname(), DIRECTORY_SEPARATOR.'generated'.DIRECTORY_SEPARATOR))
            ->filter(fn ($file): bool => in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp'], true));

        $generated = 0;

        foreach ($sources as $source) {
            $relative = str_replace(DIRECTORY_SEPARATOR, '/', ltrim(str_replace($root, '', $source->getPathname()), DIRECTORY_SEPARATOR));
            $generated += $images->generateForPublicDiskPath($relative, (bool) $this->option('missing'));
        }

        $this->info("Generated {$generated} responsive image variants from {$sources->count()} source images.");

        return self::SUCCESS;
    }
}