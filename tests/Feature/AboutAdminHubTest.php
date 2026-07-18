<?php

namespace Tests\Feature;

use App\Filament\Pages\AboutPageContent;
use App\Filament\Resources\CompanyMilestones\CompanyMilestoneResource;
use App\Filament\Resources\TeamMembers\TeamMemberResource;
use App\Models\CompanyMilestone;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\PublicSite;
use Database\Seeders\AboutPageSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AboutAdminHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_navigation_entries_are_grouped_and_ordered(): void
    {
        $groups = Filament::getPanel('admin')->getNavigationGroups();

        $this->assertSame(
            ['Operations', 'Travel Products', 'About Us', 'Content', 'Site Management'],
            array_values($groups),
        );
        $this->assertSame('About Us', AboutPageContent::getNavigationGroup());
        $this->assertSame('About Us', TeamMemberResource::getNavigationGroup());
        $this->assertSame('About Us', CompanyMilestoneResource::getNavigationGroup());
        $this->assertSame(10, AboutPageContent::getNavigationSort());
        $this->assertSame(20, TeamMemberResource::getNavigationSort());
        $this->assertSame(30, CompanyMilestoneResource::getNavigationSort());
    }

    public function test_about_hub_shows_ordered_limited_record_previews_and_actions(): void
    {
        $this->seed(AboutPageSeeder::class);
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $teams = collect(range(1, 6))->map(fn (int $number): TeamMember => TeamMember::create([
            'name' => "Team {$number}",
            'role' => ['us' => "Role {$number}"],
            'biography' => ['us' => 'Biography'],
            'portrait' => $number === 1 ? '/images/about/team-placeholder.svg' : ($number === 2 ? 'admin/about/team/uploaded.jpg' : null),
            'portrait_alt' => ['us' => "Team {$number} portrait"],
            'category' => $number === 1 ? 'leadership' : 'operations',
            'sort_order' => $number,
            'is_sample' => $number === 1,
            'is_active' => $number !== 2,
        ]));
        $milestones = collect(range(1, 6))->map(fn (int $number): CompanyMilestone => CompanyMilestone::create([
            'period' => ['us' => "20{$number}"],
            'title' => ['us' => "Milestone {$number}"],
            'description' => ['us' => 'Description'],
            'sort_order' => $number,
            'is_sample' => $number === 1,
            'is_active' => $number !== 2,
        ]));

        $component = Livewire::test(AboutPageContent::class)
            ->assertSee('Team 1')
            ->assertSee('Team 5')
            ->assertDontSee('Team 6')
            ->assertSee('1 more profile')
            ->assertSee('Milestone 1')
            ->assertSee('Milestone 5')
            ->assertDontSee('Milestone 6')
            ->assertSee('1 more milestone')
            ->assertSee('Sample')
            ->assertSee('Hidden')
            ->assertSee('Manage & reorder all');

        $component
            ->assertSee(TeamMemberResource::getUrl('create'), escape: false)
            ->assertSee(TeamMemberResource::getUrl('edit', ['record' => $teams->first()]), escape: false)
            ->assertSee(CompanyMilestoneResource::getUrl('create'), escape: false)
            ->assertSee(CompanyMilestoneResource::getUrl('edit', ['record' => $milestones->first()]), escape: false)
            ->assertSee(url(PublicSite::assetPath('/images/about/team-placeholder.svg')), escape: false)
            ->assertSee(url(PublicSite::assetPath('admin/about/team/uploaded.jpg')), escape: false);

        $teams->last()->update(['sort_order' => 0]);

        $component
            ->call('refreshRelatedPreviews')
            ->assertSee('Team 6')
            ->assertDontSee('Team 5');
    }

    public function test_about_hub_shows_empty_states(): void
    {
        $this->seed(AboutPageSeeder::class);
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(AboutPageContent::class)
            ->assertSee('No team members yet')
            ->assertSee('Add team member')
            ->assertSee('No company milestones yet')
            ->assertSee('Add milestone');
    }
}
