<?php

namespace Tests\Feature;

use App\Filament\Resources\TourPackages\Pages\EditTourPackage;
use App\Filament\Resources\TourPackages\Pages\ListTourPackages;
use App\Filament\Resources\TourPackages\TourPackageResource;
use App\Models\Booking;
use App\Models\NewsArticle;
use App\Models\PackageAvailability;
use App\Models\TourPackage;
use App\Models\User;
use App\Support\TourPackageDuplicator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Livewire\Livewire;
use Tests\TestCase;

class TourPackageDuplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_complete_safe_draft_copy(): void
    {
        $this->seed();

        $source = TourPackage::query()->where('slug', 'bromo-sunrise')->firstOrFail();
        $source->itineraryItems()->create([
            'day_number' => 2,
            'time_label' => 'Morning',
            'title' => ['us' => 'Extra stop', 'id' => 'Pemberhentian tambahan', 'cn' => null],
            'description' => ['us' => 'Extra detail', 'id' => null, 'cn' => null],
            'sort_order' => 99,
        ]);
        $source->packageAddOns()->create([
            'source_key' => 'duplicate-test-addon',
            'title' => ['us' => 'Picnic', 'id' => 'Piknik', 'cn' => null],
            'description' => ['us' => 'Prepared lunch', 'id' => null, 'cn' => null],
            'price_idr' => 150000,
            'price_usd' => 10,
            'pricing_type' => 'per_booking',
            'sort_order' => 99,
            'is_active' => true,
        ]);
        $source->priceTiers()->create([
            'min_pax' => 99,
            'max_pax' => 100,
            'price_idr' => 100000,
            'price_usd' => 7,
            'sort_order' => 99,
        ]);

        PackageAvailability::create([
            'tour_package_id' => $source->id,
            'date' => now()->addMonth()->toDateString(),
            'status' => 'available',
        ]);
        Booking::create([
            'booking_code' => 'DUPLICATE-TEST',
            'tour_package_id' => $source->id,
            'destination_id' => $source->destination_id,
        ]);
        $article = NewsArticle::query()->firstOrFail();
        $source->newsArticles()->syncWithoutDetaching($article);

        $source->refresh()->load(['itineraryItems', 'packageAddOns', 'priceTiers']);
        $previousMaximumSortOrder = (int) TourPackage::query()->max('sort_order');

        $copy = app(TourPackageDuplicator::class)->duplicate($source);

        $ignoredParentAttributes = [
            'id', 'slug', 'title', 'rating', 'review_count', 'review_source', 'testimonials',
            'sort_order', 'is_featured', 'is_active', 'created_at', 'updated_at',
        ];
        $this->assertSame(
            Arr::except($source->getAttributes(), $ignoredParentAttributes),
            Arr::except($copy->getAttributes(), $ignoredParentAttributes),
        );
        $this->assertSame('bromo-sunrise-copy', $copy->slug);
        $this->assertSame($source->title['us'].' – Copy', $copy->title['us']);
        $this->assertSame($source->title['id'], $copy->title['id']);
        $this->assertSame($source->title['cn'], $copy->title['cn']);
        $this->assertSame($source->cover_image, $copy->cover_image);
        $this->assertSame($source->gallery, $copy->gallery);
        $this->assertFalse($copy->is_active);
        $this->assertFalse($copy->is_featured);
        $this->assertNull($copy->rating);
        $this->assertSame(0, $copy->review_count);
        $this->assertNull($copy->review_source);
        $this->assertNull($copy->testimonials);
        $this->assertSame($previousMaximumSortOrder + 10, $copy->sort_order);

        $this->assertCopiedChildren($source->itineraryItems, $copy->itineraryItems);
        $this->assertCopiedChildren($source->packageAddOns, $copy->packageAddOns);
        $this->assertCopiedChildren($source->priceTiers, $copy->priceTiers);

        $this->assertTrue($source->bookings()->exists());
        $this->assertFalse($copy->bookings()->exists());
        $this->assertTrue(PackageAvailability::query()->where('tour_package_id', $source->id)->exists());
        $this->assertFalse(PackageAvailability::query()->where('tour_package_id', $copy->id)->exists());
        $this->assertTrue($source->newsArticles()->exists());
        $this->assertFalse($copy->newsArticles()->exists());

        $secondCopy = app(TourPackageDuplicator::class)->duplicate($copy);
        $this->assertSame('bromo-sunrise-copy-2', $secondCopy->slug);
        $this->assertSame($source->title['us'].' – Copy 2', $secondCopy->title['us']);
    }

    public function test_table_action_duplicates_and_redirects_to_the_draft(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@tinggaljalan.test')->firstOrFail();
        $source = TourPackage::query()->where('slug', 'bromo-sunrise')->firstOrFail();
        $this->actingAs($admin);

        $component = Livewire::test(ListTourPackages::class)
            ->callTableAction('duplicate', $source)
            ->assertHasNoTableActionErrors();

        $copy = TourPackage::query()->where('slug', 'bromo-sunrise-copy')->firstOrFail();
        $component->assertRedirect(TourPackageResource::getUrl('edit', ['record' => $copy]));
    }

    public function test_edit_header_action_duplicates_and_redirects_to_the_draft(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@tinggaljalan.test')->firstOrFail();
        $source = TourPackage::query()->where('slug', 'bromo-sunrise')->firstOrFail();
        $this->actingAs($admin);

        $component = Livewire::test(EditTourPackage::class, ['record' => $source->getRouteKey()])
            ->callAction('duplicate')
            ->assertHasNoActionErrors();

        $copy = TourPackage::query()->where('slug', 'bromo-sunrise-copy')->firstOrFail();
        $component->assertRedirect(TourPackageResource::getUrl('edit', ['record' => $copy]));
    }

    private function assertCopiedChildren($source, $copy): void
    {
        $ignoredAttributes = ['id', 'tour_package_id', 'created_at', 'updated_at'];
        $sourceAttributes = $source->map(fn ($record) => Arr::except($record->getAttributes(), $ignoredAttributes))->values()->all();
        $copyAttributes = $copy->map(fn ($record) => Arr::except($record->getAttributes(), $ignoredAttributes))->values()->all();

        $this->assertSame($sourceAttributes, $copyAttributes);
        $this->assertEmpty($source->pluck('id')->intersect($copy->pluck('id')));
    }
}
