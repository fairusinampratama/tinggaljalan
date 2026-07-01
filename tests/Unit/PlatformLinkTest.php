<?php

namespace Tests\Unit;

use App\Models\PlatformLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PlatformLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_four_platform_links_can_be_active(): void
    {
        foreach (range(1, PlatformLink::MAX_ACTIVE) as $index) {
            $this->platform($index, true);
        }

        try {
            $this->platform(5, true);
            $this->fail('A fifth active platform link was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('is_active', $exception->errors());
        }

        $inactive = $this->platform(6, false);
        $this->assertFalse($inactive->is_active);
    }

    private function platform(int $index, bool $active): PlatformLink
    {
        return PlatformLink::create([
            'name' => "Platform {$index}",
            'url' => "https://example.test/{$index}",
            'logo' => "/images/platform-{$index}.png",
            'alt' => "Platform {$index} logo",
            'sort_order' => $index,
            'is_active' => $active,
        ]);
    }
}