<?php

namespace Tests\Feature;

use App\Filament\Pages\AboutPageContent;
use App\Filament\Support\AboutPageReadiness;
use App\Filament\Support\AboutPageTranslationHelper;
use App\Models\AboutPage;
use App\Models\User;
use Database\Seeders\AboutPageSeeder;
use Database\Seeders\CompanyMilestoneSeeder;
use Database\Seeders\TeamMemberSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AboutPageEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_translation_helper_fills_only_missing_translations(): void
    {
        $state = AboutPageTranslationHelper::fillMissingFromEnglish([
            'hero' => [
                'title' => ['us' => 'Local team', 'id' => 'Tim lokal', 'cn' => ''],
                'facts' => [[
                    'label' => ['us' => 'Based in', 'id' => '', 'cn' => '所在地'],
                    'value' => ['us' => 'Malang', 'id' => '', 'cn' => ''],
                ]],
            ],
        ]);

        $this->assertSame('Tim lokal', data_get($state, 'hero.title.id'));
        $this->assertSame('Local team', data_get($state, 'hero.title.cn'));
        $this->assertSame('Based in', data_get($state, 'hero.facts.0.label.id'));
        $this->assertSame('所在地', data_get($state, 'hero.facts.0.label.cn'));
        $this->assertSame('Malang', data_get($state, 'hero.facts.0.value.cn'));
    }

    public function test_readiness_respects_disabled_sections(): void
    {
        $this->seed(AboutPageSeeder::class);
        $state = AboutPage::firstOrFail()->toArray();
        data_set($state, 'section_visibility.team', true);
        data_set($state, 'section_visibility.milestones', true);

        $missing = AboutPageReadiness::missingItemsFromState($state, activeTeamCount: 0, activeMilestoneCount: 0);
        $this->assertContains('At least one active team member', $missing);
        $this->assertContains('At least one active company milestone', $missing);

        data_set($state, 'section_visibility.team', false);
        data_set($state, 'section_visibility.milestones', false);

        $missing = AboutPageReadiness::missingItemsFromState($state, activeTeamCount: 0, activeMilestoneCount: 0);
        $this->assertNotContains('At least one active team member', $missing);
        $this->assertNotContains('At least one active company milestone', $missing);
    }

    public function test_incomplete_page_can_save_as_draft_but_cannot_be_published(): void
    {
        $this->seed([AboutPageSeeder::class, TeamMemberSeeder::class, CompanyMilestoneSeeder::class]);
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(AboutPageContent::class)
            ->set('data.is_published', false)
            ->set('data.hero.title.us', '')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse(AboutPage::firstOrFail()->is_published);

        Livewire::test(AboutPageContent::class)
            ->set('data.is_published', true)
            ->call('save')
            ->assertHasFormErrors(['is_published']);
    }
}
