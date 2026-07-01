<?php

namespace Tests\Unit;

use App\Models\TrustStat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TrustStatTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_four_trust_stats_can_be_active(): void
    {
        foreach (TrustStat::ICONS as $index => $icon) {
            $this->stat($icon, true, $index);
        }

        try {
            $this->stat('star', true, 5);
            $this->fail('A fifth active trust stat was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('is_active', $exception->errors());
        }

        $inactive = $this->stat('star', false, 6);
        $this->assertFalse($inactive->is_active);
    }

    public function test_trust_stat_icon_must_be_supported(): void
    {
        $this->expectException(ValidationException::class);

        $this->stat('unknown-icon', false, 1);
    }

    private function stat(string $icon, bool $active, int $sortOrder): TrustStat
    {
        return TrustStat::create([
            'title' => ['us' => "Trust stat {$sortOrder}"],
            'value' => ['us' => (string) $sortOrder],
            'icon_key' => $icon,
            'sort_order' => $sortOrder,
            'is_active' => $active,
        ]);
    }
}