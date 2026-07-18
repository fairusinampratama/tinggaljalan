<?php

namespace Tests\Feature;

use App\Filament\Pages\AboutPageContent;
use App\Filament\Resources\CompanyMilestones\Pages\CreateCompanyMilestone;
use App\Filament\Resources\TeamMembers\Pages\CreateTeamMember;
use App\Models\AboutPage;
use App\Models\CompanyMilestone;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\AboutPageSeeder;
use Database\Seeders\CompanyMilestoneSeeder;
use Database\Seeders\TeamMemberSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use Tests\TestCase;

class AboutPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_about_page_publishes_complete_sample_content(): void
    {
        $this->seed([AboutPageSeeder::class, TeamMemberSeeder::class, CompanyMilestoneSeeder::class]);

        $this->get('/about-us')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('AboutUsPage')
                ->where('aboutPage.isPublished', true)
                ->where('aboutPage.hero.title.us', 'From Malang, we prepare every journey down to the last detail.')
                ->has('aboutPage.values.items', 3)
                ->has('aboutPage.workflow.steps', 5)
                ->where('aboutPage.sectionVisibility.team', true)
                ->where('aboutPage.sectionVisibility.milestones', true)
                ->has('teamMembers', 4)
                ->where('teamMembers.0.isSample', true)
                ->has('milestones', 4)
                ->where('milestones.0.isSample', true)
                ->where('publicData.site.aboutEnabled', true)
                ->where('seo.canonical', fn (string $canonical): bool => str_ends_with($canonical, '/about-us')));

        $this->assertSame('founder-placeholder', TeamMember::query()->ordered()->first()->seed_key);
        $this->assertSame('beginning-placeholder', CompanyMilestone::query()->ordered()->first()->seed_key);
        $this->assertSame(4, TeamMember::query()->where('is_sample', true)->count());
        $this->assertSame(4, CompanyMilestone::query()->where('is_sample', true)->count());
    }

    public function test_about_seeders_preserve_mandarin_utf8_content(): void
    {
        $this->seed([AboutPageSeeder::class, TeamMemberSeeder::class, CompanyMilestoneSeeder::class]);

        $about = AboutPage::firstOrFail();
        $team = TeamMember::query()->ordered()->get();
        $milestones = CompanyMilestone::query()->ordered()->get();
        $serialized = json_encode([$about->toArray(), $team->toArray(), $milestones->toArray()], JSON_UNESCAPED_UNICODE);

        $this->assertIsString($serialized);
        $this->assertStringNotContainsString('???', $serialized);
        $this->assertSame('从布罗莫运营到更丰富的路线，每个阶段都源于客人的实际需求以及与本地伙伴的合作。', $about->milestones_section['intro']['cn']);
        $this->assertSame('首席行程设计师', $team->first()->role['cn']);
        $this->assertSame('第一篇章', $milestones->first()->period['cn']);
    }

    public function test_active_sample_and_confirmed_team_members_and_milestones_are_public(): void
    {
        $this->seed([AboutPageSeeder::class, TeamMemberSeeder::class, CompanyMilestoneSeeder::class]);

        TeamMember::query()
            ->where('seed_key', 'founder-placeholder')
            ->update(['name' => 'Confirmed Founder', 'is_sample' => false]);

        CompanyMilestone::query()
            ->where('seed_key', 'beginning-placeholder')
            ->update(['is_sample' => false]);

        $this->get('/about-us')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('teamMembers', 4)
                ->where('teamMembers.0.name', 'Confirmed Founder')
                ->where('teamMembers.0.isSample', false)
                ->where('teamMembers.1.isSample', true)
                ->has('milestones', 4)
                ->where('milestones.0.isSample', false)
                ->where('milestones.1.isSample', true));

        $this->assertSame(4, TeamMember::query()->count());
        $this->assertSame(4, CompanyMilestone::query()->count());
    }

    public function test_unpublished_page_returns_not_found_and_is_removed_from_shared_data_and_sitemap(): void
    {
        AboutPage::create(['seed_key' => 'default-about-page', 'is_published' => false]);

        $this->get('/about-us')->assertNotFound();
        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('publicData.site.aboutEnabled', false));
        $this->get('/sitemap.xml')->assertOk()->assertDontSee('/about-us');
    }

    public function test_about_seeders_are_idempotent_and_preserve_admin_edits(): void
    {
        $this->seed([AboutPageSeeder::class, TeamMemberSeeder::class, CompanyMilestoneSeeder::class]);
        $about = AboutPage::query()->firstOrFail();
        $hero = $about->hero;
        $hero['title']['us'] = 'Edited by admin';
        $about->update(['hero' => $hero]);
        TeamMember::query()->where('seed_key', 'founder-placeholder')->update(['name' => 'Edited Member']);

        $this->seed([AboutPageSeeder::class, TeamMemberSeeder::class, CompanyMilestoneSeeder::class]);

        $this->assertSame(1, AboutPage::count());
        $this->assertSame(4, TeamMember::count());
        $this->assertSame(4, CompanyMilestone::count());
        $this->assertSame('Edited by admin', AboutPage::firstOrFail()->hero['title']['us']);
        $this->assertSame('Edited Member', TeamMember::query()->where('seed_key', 'founder-placeholder')->value('name'));
    }

    public function test_about_page_editor_saves_localized_content_and_visibility(): void
    {
        $this->seed([AboutPageSeeder::class, TeamMemberSeeder::class, CompanyMilestoneSeeder::class]);
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        Livewire::test(AboutPageContent::class)
            ->set('data.hero.title.us', 'A real local team')
            ->set('data.hero.title.id', 'Tim lokal yang nyata')
            ->set('data.section_visibility.milestones', false)
            ->call('save')
            ->assertHasNoFormErrors();

        $about = AboutPage::firstOrFail();
        $this->assertSame('A real local team', $about->hero['title']['us']);
        $this->assertSame('Tim lokal yang nyata', $about->hero['title']['id']);
        $this->assertFalse($about->section_visibility['milestones']);
    }

    public function test_team_and_milestone_crud_create_multilingual_records(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        Livewire::test(CreateTeamMember::class)
            ->set('data.name', 'Confirmed Team Member')
            ->set('data.role.us', 'Trip Coordinator')
            ->set('data.biography.us', 'Coordinates trip details.')
            ->set('data.portrait_alt.us', 'Portrait of the trip coordinator')
            ->set('data.category', 'operations')
            ->set('data.languages', ['Indonesian', 'English'])
            ->set('data.is_sample', false)
            ->set('data.is_active', true)
            ->call('create')
            ->assertHasNoFormErrors();

        Livewire::test(CreateCompanyMilestone::class)
            ->set('data.period.us', '2026')
            ->set('data.title.us', 'A confirmed milestone')
            ->set('data.description.us', 'A verified event in the company history.')
            ->set('data.is_sample', false)
            ->set('data.is_active', true)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('team_members', ['name' => 'Confirmed Team Member', 'category' => 'operations', 'is_sample' => false]);
        $this->assertDatabaseHas('company_milestones', ['is_sample' => false, 'is_active' => true]);
    }

    public function test_sitemap_includes_published_about_page(): void
    {
        $this->seed([AboutPageSeeder::class, TeamMemberSeeder::class, CompanyMilestoneSeeder::class]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('/about-us')
            ->assertSee('<changefreq>monthly</changefreq>', false);
    }
}
