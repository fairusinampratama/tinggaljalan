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
        $this->writeSamplePng($source);

        $generated = $images->generateForPublicDiskPath('testing-responsive-images/sample.png');

        $this->assertSame(5, $generated);
        $this->assertFileExists(storage_path('app/public/generated/storage/testing-responsive-images/sample-480.webp'));
        $this->assertFileExists(storage_path('app/public/generated/storage/testing-responsive-images/sample-1600.webp'));
    }

    private function writeSamplePng(string $path): void
    {
        $image = imagecreatetruecolor(16, 12);
        $color = imagecolorallocate($image, 24, 96, 132);
        imagefilledrectangle($image, 0, 0, 15, 11, $color);
        imagepng($image, $path);
        imagedestroy($image);
    }

    public function test_native_raster_bytes_with_a_legacy_filename_generate_verified_variants_without_changing_the_original(): void
    {
        $images = app(ResponsiveImageGenerator::class);
        if (! $images->isSupported()) {
            $this->markTestSkipped('GD WebP support is unavailable in this PHP runtime.');
        }
        $encoders = ['gif' => 'imagegif', 'bmp' => 'imagebmp', 'avif' => 'imageavif'];
        $tested = 0;
        foreach ($encoders as $format => $encoder) {
            if (! function_exists($encoder)) {
                continue;
            }
            $relative = 'testing-responsive-images/native-'.$format.'.jpg';
            $source = storage_path('app/public/'.$relative);
            $image = imagecreatetruecolor(31, 19);
            imagefill($image, 0, 0, imagecolorallocate($image, 24, 96, 132));
            $this->assertTrue($encoder($image, $source));
            imagedestroy($image);
            $original = file_get_contents($source);
            $this->assertSame(5, $images->generateForPublicDiskPath($relative, true));
            foreach (ResponsiveImageGenerator::WIDTHS as $width) {
                $output = storage_path('app/public/'.$images->generatedPublicDiskPath($relative, $width));
                $info = getimagesize($output);
                $this->assertSame('image/webp', $info['mime']);
                $this->assertSame([31, 19], [$info[0], $info[1]]);
            }
            $this->assertSame(0, $images->generateForPublicDiskPath($relative, true));
            $this->assertSame($original, file_get_contents($source));
            $tested++;
        }
        $this->assertGreaterThanOrEqual(2, $tested);
    }

    public function test_unknown_or_corrupt_raster_bytes_do_not_create_responsive_output(): void
    {
        $images = app(ResponsiveImageGenerator::class);
        $fixtures = ['not-an-image', base64_decode('AAABAAEAEBAAAAAAIABSAAAAFgAAAIlQTkcNChoKAAAADUlIRFIAAAAQAAAAEAgCAAAAkJFoNgAAABlJREFUeJxjlEhoYSAFMJGkelTDqIYhpQEANZkBHPaG3+sAAAAASUVORK5CYII=')];
        foreach ($fixtures as $index => $bytes) {
            $relative = 'testing-responsive-images/corrupt-'.$index.'.jpg';
            $source = storage_path('app/public/'.$relative);
            file_put_contents($source, $bytes);
            $this->assertSame(0, @$images->generateForPublicDiskPath($relative, true));
            $this->assertFileDoesNotExist(storage_path('app/public/'.$images->generatedPublicDiskPath($relative, 480)));
            $this->assertSame($bytes, file_get_contents($source));
        }
    }

    public function test_backfill_command_generates_missing_variants_and_skips_generated_folder(): void
    {
        $images = app(ResponsiveImageGenerator::class);

        if (! $images->isSupported()) {
            $this->markTestSkipped('GD WebP support is unavailable in this PHP runtime.');
        }

        $this->writeSamplePng($this->testDirectory.'/sample.png');

        $this->artisan('images:generate-responsive', ['--missing' => true])
            ->assertSuccessful();

        $this->assertFileExists(storage_path('app/public/generated/storage/testing-responsive-images/sample-768.webp'));
    }
}
