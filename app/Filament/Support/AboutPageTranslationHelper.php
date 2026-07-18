<?php

namespace App\Filament\Support;

class AboutPageTranslationHelper
{
    public const LOCALIZED_FIELDS = [
        'hero.eyebrow', 'hero.title', 'hero.intro', 'hero.image_alt',
        'story.eyebrow', 'story.title', 'story.body', 'story.image_alt', 'story.quote',
        'values_section.eyebrow', 'values_section.title', 'values_section.intro',
        'team_section.eyebrow', 'team_section.title', 'team_section.intro', 'team_section.sample_label',
        'team_section.category_labels.leadership', 'team_section.category_labels.booking',
        'team_section.category_labels.operations', 'team_section.category_labels.field',
        'milestones_section.eyebrow', 'milestones_section.title', 'milestones_section.intro', 'milestones_section.sample_label',
        'workflow_section.eyebrow', 'workflow_section.title', 'workflow_section.intro',
        'profile_section.eyebrow', 'profile_section.title', 'profile_section.intro',
        'profile_section.operating_description', 'profile_section.legal_name_label',
        'profile_section.founding_year_label', 'profile_section.registration_label',
        'cta.title', 'cta.text', 'cta.primary_label', 'cta.secondary_label',
        'seo.title', 'seo.description',
    ];

    /** @var array<string, array<int, string>> */
    public const LOCALIZED_REPEATERS = [
        'hero.facts' => ['label', 'value'],
        'values_section.items' => ['title', 'text'],
        'workflow_section.steps' => ['title', 'text'],
    ];

    public static function fillMissingFromEnglish(array $state): array
    {
        foreach (self::LOCALIZED_FIELDS as $field) {
            $value = (array) data_get($state, $field, []);
            $english = $value['us'] ?? null;

            foreach (['id', 'cn'] as $language) {
                if (blank($value[$language] ?? null) && filled($english)) {
                    $value[$language] = $english;
                }
            }

            data_set($state, $field, $value);
        }

        foreach (self::LOCALIZED_REPEATERS as $field => $localizedKeys) {
            $items = (array) data_get($state, $field, []);

            foreach ($items as $index => $item) {
                foreach ($localizedKeys as $localizedKey) {
                    $value = (array) data_get($item, $localizedKey, []);
                    $english = $value['us'] ?? null;

                    foreach (['id', 'cn'] as $language) {
                        if (blank($value[$language] ?? null) && filled($english)) {
                            $value[$language] = $english;
                        }
                    }

                    data_set($items[$index], $localizedKey, $value);
                }
            }

            data_set($state, $field, $items);
        }

        return $state;
    }
}
