<?php

namespace Tests\Feature;

use App\Support\ResponsiveImageGenerator;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ResponsiveImageGeneratorTest extends TestCase
{
    private string $testDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDirectory = storage_path('app/public/testing-responsive-images');
        File::deleteDirectory($this->testDirectory);
        File::ensureDirectoryExists($this->testDirectory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->testDirectory);
        File::deleteDirectory(storage_path('app/public/generated/storage/testing-responsive-images'));

        parent::tearDown();
    }

    public function test_generated_public_disk_path_matches_frontend_contract(): void
    {
        $images = app(ResponsiveImageGenerator::class);

        $this->assertSame(
            'generated/storage/admin/hero/example-768.webp',
            $images->generatedPublicDiskPath('admin/hero/example.jpg', 768),
        );

        $this->assertSame(
            'generated/storage/admin/hero/example-768.webp',
            $images->generatedPublicDiskPath('/storage/admin/hero/example.jpg', 768),
        );
    }

    public function test_generator_creates_webp_variants_for_uploaded_public_storage_images(): void
    {
        $images = app(ResponsiveImageGenerator::class);

        if (! $images->isSupported()) {
            $this->markTestSkipped('GD WebP support is unavailable in this PHP runtime.');
        }

        $source = $this->testDirectory.'/sample.png';
        file_put_contents($source, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAQAAAAECAIAAAAmkwkpAAAAFElEQVR4nGP8z4AATAxEcDgBTykAObACpInwnaEAAAAASUVORK5CYII='
        ));

        $generated = $images->generateForPublicDiskPath('testing-responsive-images/sample.png');

        $this->assertSame(5, $generated);
        $this->assertFileExists(storage_path('app/public/generated/storage/testing-responsive-images/sample-480.webp'));
        $this->assertFileExists(storage_path('app/public/generated/storage/testing-responsive-images/sample-1600.webp'));
    }

    public function test_backfill_command_generates_missing_variants_and_skips_generated_folder(): void
    {
        $images = app(ResponsiveImageGenerator::class);

        if (! $images->isSupported()) {
            $this->markTestSkipped('GD WebP support is unavailable in this PHP runtime.');
        }

        file_put_contents($this->testDirectory.'/sample.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAQAAAAECAIAAAAmkwkpAAAAFElEQVR4nGP8z4AATAxEcDgBTykAObACpInwnaEAAAAASUVORK5CYII='
        ));

        $this->artisan('images:generate-responsive', ['--missing' => true])
            ->assertSuccessful();

        $this->assertFileExists(storage_path('app/public/generated/storage/testing-responsive-images/sample-768.webp'));
    }
}