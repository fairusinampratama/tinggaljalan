<?php

namespace Tests\Feature;

use App\Models\PackageAvailability;
use App\Models\TourPackage;
use App\Support\PackageAvailabilityResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PackageAvailabilityRangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_destination_closure_range_and_open_ended_closure_are_resolved(): void
    {
        $this->seed();

        $package = TourPackage::where('slug', 'bromo-sunrise')->firstOrFail();
        PackageAvailability::query()
            ->where('destination_id', $package->destination_id)
            ->orWhere('tour_package_id', $package->id)
            ->delete();

        PackageAvailability::create([
            'destination_id' => $package->destination_id,
            'date' => '2026-07-10',
            'end_date' => '2026-07-15',
            'status' => 'blocked',
            'reason' => 'Temporary volcanic closure.',
        ]);

        $resolver = app(PackageAvailabilityResolver::class);

        $this->assertNull($resolver->resolve($package, '2026-07-09'));
        $this->assertSame('blocked', $resolver->resolve($package, '2026-07-10')?->status);
        $this->assertSame('blocked', $resolver->resolve($package, '2026-07-15')?->status);
        $this->assertNull($resolver->resolve($package, '2026-07-16'));

        PackageAvailability::query()->delete();
        PackageAvailability::create([
            'destination_id' => $package->destination_id,
            'date' => '2026-07-20',
            'is_open_ended' => true,
            'status' => 'blocked',
        ]);

        $this->assertSame('blocked', $resolver->resolve($package, '2030-01-01')?->status);
    }

    public function test_package_range_overrides_destination_range(): void
    {
        $this->seed();

        $package = TourPackage::where('slug', 'bromo-sunrise')->firstOrFail();
        PackageAvailability::query()->delete();

        PackageAvailability::create([
            'destination_id' => $package->destination_id,
            'date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'status' => 'blocked',
        ]);
        PackageAvailability::create([
            'tour_package_id' => $package->id,
            'date' => '2026-08-10',
            'end_date' => '2026-08-12',
            'status' => 'available',
        ]);

        $resolver = app(PackageAvailabilityResolver::class);

        $this->assertSame('blocked', $resolver->resolve($package, '2026-08-09')?->status);
        $this->assertSame('available', $resolver->resolve($package, '2026-08-11')?->status);
    }

    public function test_overlapping_ranges_in_the_same_scope_are_rejected(): void
    {
        $this->seed();

        $package = TourPackage::where('slug', 'bromo-sunrise')->firstOrFail();
        PackageAvailability::query()->delete();

        PackageAvailability::create([
            'destination_id' => $package->destination_id,
            'date' => '2026-09-01',
            'end_date' => '2026-09-10',
            'status' => 'blocked',
        ]);

        $this->expectException(ValidationException::class);

        PackageAvailability::create([
            'destination_id' => $package->destination_id,
            'date' => '2026-09-05',
            'end_date' => '2026-09-12',
            'status' => 'blocked',
        ]);
    }
}
