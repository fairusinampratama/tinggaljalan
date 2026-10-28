<?php

namespace Tests\Feature;

use App\Filament\Resources\TourPackages\Pages\EditTourPackage;
use App\Models\TourPackage;
use App\Models\User;
use App\Support\InertiaPublicData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductItineraryGalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_structured_itinerary_preserves_saved_order_times_and_localized_content(): void
    {
        $this->seed();
        $package = TourPackage::where('slug', 'bromo-sunrise')->firstOrFail();
        $package->itineraryItems()->delete();
        foreach (['23:00', '00:15', null, 'Morning'] as $index => $time) {
            $package->itineraryItems()->create(['day_number' => $index < 3 ? 1 : 2,
                'sort_order' => $index, 'time_label' => $time,
                'title' => ['us' => 'Activity '.$index, 'id' => 'Kegiatan '.$index],
                'description' => ['us' => str_repeat('Complete description. ', 30)]]);
        }
        $data = InertiaPublicData::route($package->fresh());
        $this->assertSame(['23:00', '00:15', null, 'Morning'], array_column($data['itinerarySteps'], 'timeLabel'));
        $this->assertSame([1, 1, 1, 2], array_column($data['itinerarySteps'], 'dayNumber'));
        $this->assertSame('Activity 0', $data['itinerarySteps'][0]['title']['cn']);
        $this->assertSame('Kegiatan 0', $data['itinerarySteps'][0]['title']['id']);
        $this->assertCount(4, $data['itinerary']);
        foreach (['us', 'id', 'cn'] as $language) {
            $this->get('/routes/bromo-sunrise?lang='.$language)->assertOk()
                ->assertSee('server-itinerary-timeline')->assertSee('23:00')->assertSee('00:15')
                ->assertSee('Morning')->assertSee('Complete description.');
        }
    }

    public function test_twenty_gallery_references_can_be_saved_reopened_reordered_and_replaced_without_deleting_files(): void
    {
        Storage::fake('public');
        $this->seed();
        $this->actingAs(User::where('email', 'admin@tinggaljalan.test')->firstOrFail());
        $package = TourPackage::where('slug', 'bromo-sunrise')->firstOrFail();
        $package->update(['status' => 'draft']);
        $paths = [];
        for ($i = 0; $i < 21; $i++) {
            $paths[] = 'admin/packages/gallery/review-'.$i.'.jpg';
            Storage::disk('public')->put($paths[$i], 'synthetic-reference');
        }
        $gallery = array_slice($paths, 0, 20);
        Livewire::test(EditTourPackage::class, ['record' => $package->id])
            ->fillForm(['gallery' => $gallery])->call('save')->assertHasNoFormErrors();
        $this->assertSame($gallery, $package->fresh()->gallery);
        Livewire::test(EditTourPackage::class, ['record' => $package->id])->call('save')->assertHasNoFormErrors();
        $this->assertSame($gallery, $package->fresh()->gallery);
        $gallery = array_reverse($gallery);
        $gallery[0] = $paths[20];
        Livewire::test(EditTourPackage::class, ['record' => $package->id])
            ->fillForm(['gallery' => $gallery])->call('save')->assertHasNoFormErrors();
        $this->assertSame($gallery, $package->fresh()->gallery);
        Livewire::test(EditTourPackage::class, ['record' => $package->id])
            ->fillForm(['gallery' => $paths])->call('save')->assertHasFormErrors();
        $this->assertSame($gallery, $package->fresh()->gallery);
        foreach ($paths as $file) {
            Storage::disk('public')->assertExists($file);
        }
    }
}
