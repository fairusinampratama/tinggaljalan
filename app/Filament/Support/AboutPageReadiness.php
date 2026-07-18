<?php

namespace App\Filament\Support;

use App\Models\CompanyMilestone;
use App\Models\TeamMember;

class AboutPageReadiness
{
    /**
     * @return array<int, string>
     */
    public static function missingItemsFromState(array $state, ?int $activeTeamCount = null, ?int $activeMilestoneCount = null): array
    {
        $visibility = (array) ($state['section_visibility'] ?? []);
        $missing = [];

        self::require($missing, $state, 'hero.title.us', 'Hero heading');
        self::require($missing, $state, 'hero.intro.us', 'Hero introduction');
        self::require($missing, $state, 'hero.image', 'Hero image');
        self::require($missing, $state, 'hero.image_alt.us', 'Hero image alt text');

        if (self::visible($visibility, 'story')) {
            self::require($missing, $state, 'story.title.us', 'Story heading');
            self::require($missing, $state, 'story.body.us', 'Company story');
        }

        if (self::visible($visibility, 'values')) {
            self::require($missing, $state, 'values_section.title.us', 'Values heading');

            if (! self::hasCompleteRepeaterItem($state, 'values_section.items', ['icon', 'title.us', 'text.us'])) {
                $missing[] = 'At least one complete value';
            } elseif (! self::allRepeaterItemsComplete($state, 'values_section.items', ['icon', 'title.us', 'text.us'])) {
                $missing[] = 'Complete every value card';
            }
        }

        if (self::visible($visibility, 'team')) {
            self::require($missing, $state, 'team_section.title.us', 'Team heading');

            $activeTeamCount ??= TeamMember::query()->active()->count();
            if ($activeTeamCount < 1) {
                $missing[] = 'At least one active team member';
            }
        }

        if (self::visible($visibility, 'milestones')) {
            self::require($missing, $state, 'milestones_section.title.us', 'History heading');

            $activeMilestoneCount ??= CompanyMilestone::query()->active()->count();
            if ($activeMilestoneCount < 1) {
                $missing[] = 'At least one active company milestone';
            }
        }

        if (self::visible($visibility, 'workflow')) {
            self::require($missing, $state, 'workflow_section.title.us', 'How we work heading');

            if (! self::hasCompleteRepeaterItem($state, 'workflow_section.steps', ['icon', 'title.us', 'text.us'])) {
                $missing[] = 'At least one complete workflow step';
            } elseif (! self::allRepeaterItemsComplete($state, 'workflow_section.steps', ['icon', 'title.us', 'text.us'])) {
                $missing[] = 'Complete every workflow step';
            }
        }

        if (self::visible($visibility, 'profile')) {
            self::require($missing, $state, 'profile_section.title.us', 'Company profile heading');
            self::require($missing, $state, 'profile_section.operating_description.us', 'Company operating description');
        }

        if (self::visible($visibility, 'cta')) {
            self::require($missing, $state, 'cta.title.us', 'Closing CTA heading');
            self::require($missing, $state, 'cta.primary_label.us', 'Primary CTA button label');
            self::require($missing, $state, 'cta.primary_url', 'Primary CTA destination');

            $destination = trim((string) data_get($state, 'cta.primary_url'));
            if ($destination !== '' && ! self::validDestination($destination)) {
                $missing[] = 'Valid primary CTA destination';
            }
        }

        self::require($missing, $state, 'seo.title.us', 'SEO title');
        self::require($missing, $state, 'seo.description.us', 'SEO description');

        return array_values(array_unique($missing));
    }

    public static function isReady(array $state): bool
    {
        return self::missingItemsFromState($state) === [];
    }

    private static function visible(array $visibility, string $section): bool
    {
        return ($visibility[$section] ?? true) !== false;
    }

    private static function require(array &$missing, array $state, string $path, string $label): void
    {
        if (blank(data_get($state, $path))) {
            $missing[] = $label;
        }
    }

    /**
     * @param  array<int, string>  $requiredPaths
     */
    private static function hasCompleteRepeaterItem(array $state, string $path, array $requiredPaths): bool
    {
        foreach ((array) data_get($state, $path, []) as $item) {
            if (collect($requiredPaths)->every(fn (string $requiredPath): bool => filled(data_get($item, $requiredPath)))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string>  $requiredPaths
     */
    private static function allRepeaterItemsComplete(array $state, string $path, array $requiredPaths): bool
    {
        $items = (array) data_get($state, $path, []);

        return collect($items)->every(
            fn ($item): bool => collect($requiredPaths)->every(fn (string $requiredPath): bool => filled(data_get($item, $requiredPath)))
        );
    }

    private static function validDestination(string $destination): bool
    {
        return preg_match('/^(?:\/(?!\/)\S*|https:\/\/\S+)$/i', $destination) === 1;
    }
}
