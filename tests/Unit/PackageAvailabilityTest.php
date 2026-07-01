<?php

namespace Tests\Unit;

use App\Models\Destination;
use App\Models\PackageAvailability;
use App\Models\TourPackage;
use App\Support\PackageAvailabilityResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PackageAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_package_rule_overrides_destination_rule_for_the_same_date(): void
    {
        [$destination, $package] = $this->travelProduct();

        PackageAvailability::create([
            'destination_id' => $destination->id,
            'date' => '2026-08-10',
            'status' => 'limited',
            'seats_left' => 4,
        ]);
        $packageRule = PackageAvailability::create([
            'tour_package_id' => $package->id,
            'date' => '2026-08-10',
            'status' => 'blocked',
            'reason' => 'Package override',
        ]);

        $resolver = app(PackageAvailabilityResolver::class);

        $this->assertTrue($packageRule->is($resolver->resolve($package, '2026-08-10')));
        $this->assertTrue($packageRule->is($resolver->rulesByDate($package)->get('2026-08-10')));
    }

    public function test_availability_requires_exactly_one_scope(): void
    {
        [$destination, $package] = $this->travelProduct();

        foreach ([
            ['date' => '2026-08-11', 'status' => 'available'],
            ['destination_id' => $destination->id, 'tour_package_id' => $package->id, 'date' => '2026-08-12', 'status' => 'available'],
        ] as $attributes) {
            try {
                PackageAvailability::create($attributes);
                $this->fail('Ambiguous availability scope was accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('scope_type', $exception->errors());
            }
        }
    }

    public function test_duplicate_scope_date_is_rejected(): void
    {
        [$destination] = $this->travelProduct();

        PackageAvailability::create([
            'destination_id' => $destination->id,
            'date' => '2026-08-13',
            'status' => 'available',
        ]);

        $this->expectException(ValidationException::class);

        PackageAvailability::create([
            'destination_id' => $destination->id,
            'date' => '2026-08-13',
            'status' => 'blocked',
        ]);
    }

    public function test_limited_status_requires_positive_capacity_and_other_statuses_clear_it(): void
    {
        [$destination] = $this->travelProduct();

        try {
            PackageAvailability::create([
                'destination_id' => $destination->id,
                'date' => '2026-08-14',
                'status' => 'limited',
                'seats_left' => 0,
            ]);
            $this->fail('Invalid limited capacity was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('seats_left', $exception->errors());
        }

        $available = PackageAvailability::create([
            'destination_id' => $destination->id,
            'date' => '2026-08-15',
            'status' => 'available',
            'seats_left' => 10,
        ]);

        $this->assertNull($available->seats_left);
    }

    /**
     * @return array{Destination, TourPackage}
     */
    private function travelProduct(): array
    {
        $destination = Destination::create([
            'slug' => 'availability-test',
            'name' => 'Availability Test',
            'is_active' => true,
        ]);
        $package = TourPackage::create([
            'destination_id' => $destination->id,
            'slug' => 'availability-test-package',
            'title' => ['us' => 'Availability Test Package'],
            'base_price_idr' => 500000,
            'base_price_usd' => 35,
            'is_active' => true,
        ]);

        return [$destination, $package];
    }
}