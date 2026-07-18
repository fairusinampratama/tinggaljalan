<?php

namespace App\Filament\Pages\Schemas;

use App\Filament\Resources\CompanyMilestones\CompanyMilestoneResource;
use App\Filament\Resources\TeamMembers\TeamMemberResource;
use App\Filament\Support\AboutPageReadiness;
use App\Filament\Support\AboutPageTranslationHelper;
use App\Filament\Support\AdminForm;
use App\Models\CompanyMilestone;
use App\Models\TeamMember;
use App\Support\PublicSite;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class AboutPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make([
                Step::make('First impression')->description('The opening message, image, and quick facts visitors see first.')->schema(self::heroSchema())->columns(2),
                Step::make('Story & values')->description('Explain where TinggalJalan came from and what guides the team.')->schema(self::storyValuesSchema())->columns(2),
                Step::make('Team & history')->description('Introduce the people behind the business and its milestones.')->schema(self::teamHistorySchema())->columns(2),
                Step::make('How we work')->description('Describe the customer journey from first message to completed trip.')->schema(self::workflowSchema())->columns(2),
                Step::make('Company details & CTA')->description('Public company facts, verification details, and the closing next step.')->schema(self::companyCtaSchema())->columns(2),
                Step::make('Optional translations')->description('Leave fields empty to use the English content automatically.')->schema(self::translationSchema())->columns(2),
                Step::make('Review & publish')->description('Choose visible sections, complete SEO, and confirm publication readiness.')->schema(self::reviewPublishSchema())->columns(2),
            ])->skippable()->persistStepInQueryString('about-page-step')->columnSpanFull(),
        ]);
    }

    private static function heroSchema(): array
    {
        return [
            Section::make('Opening message')
                ->description('This copy appears at the top of /about-us. Keep it human, specific, and easy to scan.')
                ->schema([
                    AdminForm::primaryLocalizedField('hero.eyebrow', 'Eyebrow')->helperText('A short label above the main heading.'),
                    AdminForm::primaryLocalizedField('hero.title', 'Heading')->helperText('The main promise visitors should understand immediately.'),
                    AdminForm::primaryLocalizedField('hero.intro', 'Introduction', textarea: true)->helperText('Explain who TinggalJalan is and where the team operates.')->rows(4)->columnSpanFull(),
                    AdminForm::imageUpload('hero.image', 'Hero team image', 'admin/about')->helperText('Use a real team, office, or trip-operations image.')->columnSpanFull(),
                    AdminForm::primaryLocalizedField('hero.image_alt', 'Image description')->helperText('Describe the visible people or scene for accessibility.')->columnSpanFull(),
                ])->columns(2)->columnSpanFull(),
            Section::make('Quick facts')
                ->description('Small proof points displayed with the hero. Reorder them to control their public order.')
                ->schema([
                    Repeater::make('hero.facts')->label('Fact chips')->schema([
                        Select::make('icon')->options(self::iconOptions()),
                        TextInput::make('label.us')->label('Label')->placeholder('Based in'),
                        TextInput::make('value.us')->label('Value')->placeholder('Malang, East Java'),
                        self::factTranslations(),
                    ])->itemLabel(fn (array $state): ?string => data_get($state, 'label.us'))->columns(2)->defaultItems(0)->reorderable()->reorderableWithButtons()->columnSpanFull(),
                ])->columnSpanFull(),
        ];
    }

    private static function storyValuesSchema(): array
    {
        return [
            Section::make('Company story')->description('Tell the verified story behind the business in plain language.')->schema([
                AdminForm::primaryLocalizedField('story.eyebrow', 'Eyebrow'),
                AdminForm::primaryLocalizedField('story.title', 'Heading'),
                AdminForm::primaryLocalizedField('story.body', 'Story', textarea: true)->helperText('Explain the origin, local connection, and work the team does today.')->rows(7)->columnSpanFull(),
                AdminForm::imageUpload('story.image', 'Story or operations image', 'admin/about')->helperText('Optional supporting image shown beside the story.')->columnSpanFull(),
                AdminForm::primaryLocalizedField('story.image_alt', 'Image description')->columnSpanFull(),
                AdminForm::primaryLocalizedField('story.quote', 'Team quote', textarea: true)->rows(3)->columnSpanFull(),
                TextInput::make('story.quote_author')->label('Quote attribution')->helperText('A real person, role, or “TinggalJalan team”.')->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
            Section::make('Values')->description('The principles customers should experience when working with TinggalJalan.')->schema([
                AdminForm::primaryLocalizedField('values_section.eyebrow', 'Eyebrow'),
                AdminForm::primaryLocalizedField('values_section.title', 'Heading'),
                AdminForm::primaryLocalizedField('values_section.intro', 'Introduction', textarea: true)->rows(3)->columnSpanFull(),
                Repeater::make('values_section.items')->label('Value cards')->schema([
                    Select::make('icon')->options(self::iconOptions()),
                    TextInput::make('title.us')->label('Title'),
                    Textarea::make('text.us')->label('Description')->rows(3)->columnSpanFull(),
                    self::repeaterTranslations('title', 'Title', 'text', 'Description'),
                ])->itemLabel(fn (array $state): ?string => data_get($state, 'title.us'))->columns(2)->defaultItems(0)->reorderable()->reorderableWithButtons()->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
        ];
    }

    private static function teamHistorySchema(): array
    {
        return [
            Section::make('Team section')
                ->description('Edit the introduction here. Individual people stay in the sortable Team Members area.')
                ->afterHeader([
                    Action::make('refreshRelatedPreviews')
                        ->label('Refresh previews')
                        ->icon('heroicon-o-arrow-path')
                        ->action('refreshRelatedPreviews'),
                ])
                ->schema([
                    View::make('filament.forms.components.about-team-preview')->viewData(fn (): array => self::teamPreviewData())->columnSpanFull(),
                    AdminForm::primaryLocalizedField('team_section.eyebrow', 'Eyebrow'),
                    AdminForm::primaryLocalizedField('team_section.title', 'Heading'),
                    AdminForm::primaryLocalizedField('team_section.intro', 'Introduction', textarea: true)->rows(3)->columnSpanFull(),
                    AdminForm::primaryLocalizedField('team_section.sample_label', 'Sample profile marker')->helperText('Shown on profiles still marked as sample content.')->columnSpanFull(),
                    Fieldset::make('Division headings')->schema([
                        AdminForm::primaryLocalizedField('team_section.category_labels.leadership', 'Founder & leadership'),
                        AdminForm::primaryLocalizedField('team_section.category_labels.booking', 'Booking & communication'),
                        AdminForm::primaryLocalizedField('team_section.category_labels.operations', 'Trip operations'),
                        AdminForm::primaryLocalizedField('team_section.category_labels.field', 'Field partners'),
                    ])->columns(2)->columnSpanFull(),
                ])->columns(2)->columnSpanFull(),
            Section::make('Company history')
                ->description('Edit the introduction here. Individual entries stay in the sortable Company Milestones area.')
                ->schema([
                    View::make('filament.forms.components.about-milestone-preview')->viewData(fn (): array => self::milestonePreviewData())->columnSpanFull(),
                    AdminForm::primaryLocalizedField('milestones_section.eyebrow', 'Eyebrow'),
                    AdminForm::primaryLocalizedField('milestones_section.title', 'Heading'),
                    AdminForm::primaryLocalizedField('milestones_section.intro', 'Introduction', textarea: true)->rows(3)->columnSpanFull(),
                    AdminForm::primaryLocalizedField('milestones_section.sample_label', 'Sample milestone marker')->helperText('Shown on entries still marked as sample content.')->columnSpanFull(),
                ])->columns(2)->columnSpanFull(),
        ];
    }

    private static function workflowSchema(): array
    {
        return [
            Section::make('How we work')->description('The cards appear publicly in this exact order. Describe the real booking and trip process.')->schema([
                AdminForm::primaryLocalizedField('workflow_section.eyebrow', 'Eyebrow'),
                AdminForm::primaryLocalizedField('workflow_section.title', 'Heading'),
                AdminForm::primaryLocalizedField('workflow_section.intro', 'Introduction', textarea: true)->rows(3)->columnSpanFull(),
                Repeater::make('workflow_section.steps')->label('Workflow steps')->schema([
                    Select::make('icon')->options(self::iconOptions()),
                    TextInput::make('title.us')->label('Step title'),
                    Textarea::make('text.us')->label('Step description')->rows(3)->columnSpanFull(),
                    self::repeaterTranslations('title', 'Step title', 'text', 'Step description'),
                ])->itemLabel(fn (array $state): ?string => data_get($state, 'title.us'))->columns(2)->defaultItems(0)->reorderable()->reorderableWithButtons()->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
        ];
    }

    private static function companyCtaSchema(): array
    {
        return [
            Section::make('Public introduction')
                ->description('Contact details, service areas, and maps come from Site Details; marketplace profiles come from Platform Links.')
                ->schema([
                    AdminForm::primaryLocalizedField('profile_section.eyebrow', 'Eyebrow'),
                    AdminForm::primaryLocalizedField('profile_section.title', 'Heading'),
                    AdminForm::primaryLocalizedField('profile_section.intro', 'Introduction', textarea: true)->rows(3)->columnSpanFull(),
                    AdminForm::primaryLocalizedField('profile_section.operating_description', 'Operating description', textarea: true)->helperText('Describe the real operating area, support model, and local coordination.')->rows(5)->columnSpanFull(),
                ])->columns(2)->columnSpanFull(),
            Section::make('Verification details')->description('Only enable a detail after it has been confirmed and is safe to publish.')->schema([
                Grid::make(2)->schema([
                    TextInput::make('profile_section.legal_name')->label('Legal name'),
                    Toggle::make('profile_section.show_legal_name')->label('Show legal name publicly'),
                    TextInput::make('profile_section.founding_year')->label('Founding year'),
                    Toggle::make('profile_section.show_founding_year')->label('Show founding year publicly'),
                    TextInput::make('profile_section.registration')->label('Registration details'),
                    Toggle::make('profile_section.show_registration')->label('Show registration publicly'),
                ])->columnSpanFull(),
                Fieldset::make('Public labels')->schema([
                    AdminForm::primaryLocalizedField('profile_section.legal_name_label', 'Legal name label'),
                    AdminForm::primaryLocalizedField('profile_section.founding_year_label', 'Founding year label'),
                    AdminForm::primaryLocalizedField('profile_section.registration_label', 'Registration label'),
                ])->columns(3)->columnSpanFull(),
            ])->columnSpanFull(),
            Section::make('Closing call to action')->description('Give visitors one clear next step after they have learned about the company.')->schema([
                AdminForm::primaryLocalizedField('cta.title', 'Heading'),
                AdminForm::primaryLocalizedField('cta.text', 'Description', textarea: true)->rows(3)->columnSpanFull(),
                AdminForm::primaryLocalizedField('cta.primary_label', 'Primary button label'),
                TextInput::make('cta.primary_url')->label('Primary destination')->helperText('Use an internal path such as /routes or a complete https:// URL.')->regex('/^(?:\/(?!\/)[^\s]*|https:\/\/[^\s]+)$/i'),
                AdminForm::primaryLocalizedField('cta.secondary_label', 'Secondary button label'),
                TextInput::make('cta.secondary_url')->label('Secondary destination')->helperText('Use “whatsapp” for the configured WhatsApp contact, an internal /path, or a complete https:// URL.')->regex('/^(?:whatsapp|\/(?!\/)[^\s]*|https:\/\/[^\s]+)$/i'),
            ])->columns(2)->columnSpanFull(),
        ];
    }

    private static function translationSchema(): array
    {
        return [
            Section::make('Translation tools')
                ->description('Empty fields use English automatically. Copying English fills only missing translations, but copied text becomes independent and will not follow later English edits.')
                ->afterHeader([self::copyEnglishTranslationsAction()])
                ->schema([Placeholder::make('translation_note')->label('Repeated cards')->content('Fact, value, and workflow-card translations are edited inside each card on its content step.')->columnSpanFull()])
                ->columnSpanFull(),
            self::translationSection('First impression', [
                ['hero.eyebrow', 'Eyebrow'], ['hero.title', 'Heading'], ['hero.intro', 'Introduction', true], ['hero.image_alt', 'Image description'],
            ]),
            self::translationSection('Story & values', [
                ['story.eyebrow', 'Story eyebrow'], ['story.title', 'Story heading'], ['story.body', 'Company story', true], ['story.image_alt', 'Story image description'], ['story.quote', 'Team quote', true],
                ['values_section.eyebrow', 'Values eyebrow'], ['values_section.title', 'Values heading'], ['values_section.intro', 'Values introduction', true],
            ]),
            self::translationSection('Team & history', [
                ['team_section.eyebrow', 'Team eyebrow'], ['team_section.title', 'Team heading'], ['team_section.intro', 'Team introduction', true], ['team_section.sample_label', 'Sample profile marker'],
                ['team_section.category_labels.leadership', 'Founder & leadership'], ['team_section.category_labels.booking', 'Booking & communication'], ['team_section.category_labels.operations', 'Trip operations'], ['team_section.category_labels.field', 'Field partners'],
                ['milestones_section.eyebrow', 'History eyebrow'], ['milestones_section.title', 'History heading'], ['milestones_section.intro', 'History introduction', true], ['milestones_section.sample_label', 'Sample milestone marker'],
            ]),
            self::translationSection('How we work', [
                ['workflow_section.eyebrow', 'Eyebrow'], ['workflow_section.title', 'Heading'], ['workflow_section.intro', 'Introduction', true],
            ]),
            self::translationSection('Company details & CTA', [
                ['profile_section.eyebrow', 'Company eyebrow'], ['profile_section.title', 'Company heading'], ['profile_section.intro', 'Company introduction', true], ['profile_section.operating_description', 'Operating description', true],
                ['profile_section.legal_name_label', 'Legal name label'], ['profile_section.founding_year_label', 'Founding year label'], ['profile_section.registration_label', 'Registration label'],
                ['cta.title', 'CTA heading'], ['cta.text', 'CTA description', true], ['cta.primary_label', 'Primary button label'], ['cta.secondary_label', 'Secondary button label'],
            ]),
            self::translationSection('SEO', [['seo.title', 'SEO title'], ['seo.description', 'SEO description', true]]),
        ];
    }

    private static function reviewPublishSchema(): array
    {
        return [
            Section::make('Section visibility')->description('Disabled sections are hidden publicly and do not block publication.')->schema([
                Toggle::make('section_visibility.story')->label('Show company story')->default(true)->live(),
                Toggle::make('section_visibility.values')->label('Show values')->default(true)->live(),
                Toggle::make('section_visibility.milestones')->label('Show history timeline')->default(true)->live(),
                Toggle::make('section_visibility.team')->label('Show team')->default(true)->live(),
                Toggle::make('section_visibility.workflow')->label('Show how we work')->default(true)->live(),
                Toggle::make('section_visibility.profile')->label('Show company profile')->default(true)->live(),
                Toggle::make('section_visibility.cta')->label('Show closing call to action')->default(true)->live(),
            ])->columns(2)->columnSpanFull(),
            Section::make('Search and sharing')->description('Used by search engines and when the About page is shared.')->schema([
                AdminForm::primaryLocalizedField('seo.title', 'SEO title')->helperText('Aim for a clear title under roughly 60 characters.'),
                AdminForm::primaryLocalizedField('seo.description', 'SEO description', textarea: true)->helperText('Summarize who TinggalJalan is and why visitors can trust the company.')->rows(4)->columnSpanFull(),
                AdminForm::imageUpload('seo.image', 'Social sharing image', 'admin/about')->helperText('Optional. The hero image is used when this is empty.')->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
            Section::make('Publication readiness')->description('The page can be saved as an incomplete draft. Publishing requires enabled sections to be ready.')->schema([
                Placeholder::make('readiness_status')->label('Content status')->content(fn (Get $get): string => self::readinessSummary($get))->columnSpanFull(),
                Toggle::make('is_published')->label('Publish About page')->helperText('When enabled, About appears in navigation and /about-us becomes public.')->live()->rules([
                    fn (Get $get): \Closure => function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                        if (! $value) {
                            return;
                        }
                        $missing = AboutPageReadiness::missingItemsFromState(self::pageState($get));
                        if ($missing !== []) {
                            $fail('Cannot publish. Complete the following: '.implode(', ', $missing).'.');
                        }
                    },
                ]),
            ])->columnSpanFull(),
        ];
    }

    private static function factTranslations(): Section
    {
        return Section::make('Translations')->description('Optional. Empty values use English automatically.')->schema([
            TextInput::make('label.id')->label('Label - Indonesian'), TextInput::make('label.cn')->label('Label - Chinese'),
            TextInput::make('value.id')->label('Value - Indonesian'), TextInput::make('value.cn')->label('Value - Chinese'),
        ])->columns(2)->collapsed()->collapsible()->columnSpanFull();
    }

    private static function repeaterTranslations(string $firstField, string $firstLabel, string $secondField, string $secondLabel): Section
    {
        return Section::make('Translations')->description('Optional. Empty values use English automatically.')->schema([
            TextInput::make("{$firstField}.id")->label("{$firstLabel} - Indonesian"), TextInput::make("{$firstField}.cn")->label("{$firstLabel} - Chinese"),
            Textarea::make("{$secondField}.id")->label("{$secondLabel} - Indonesian")->rows(3), Textarea::make("{$secondField}.cn")->label("{$secondLabel} - Chinese")->rows(3),
        ])->columns(2)->collapsed()->collapsible()->columnSpanFull();
    }

    /** @param array<int, array{0: string, 1: string, 2?: bool}> $fields */
    private static function translationSection(string $title, array $fields): Section
    {
        $components = [];
        foreach ($fields as $field) {
            array_push($components, ...AdminForm::translationFields($field[0], $field[1], $field[2] ?? false));
        }

        return Section::make($title)->description('Only enter text when this language should differ from English.')->schema($components)->columns(2)->collapsed()->collapsible()->columnSpanFull();
    }

    private static function copyEnglishTranslationsAction(): Action
    {
        return Action::make('copyEnglishToMissingAboutTranslations')->label('Copy English to missing translations')->requiresConfirmation()
            ->modalHeading('Copy English into empty translation fields?')->modalDescription('This fills only empty Indonesian and Chinese fields. Existing translations will not be changed.')
            ->action(function (Get $get, Set $set): void {
                $state = [];
                foreach (AboutPageTranslationHelper::LOCALIZED_FIELDS as $field) {
                    data_set($state, $field, $get($field));
                }
                foreach (array_keys(AboutPageTranslationHelper::LOCALIZED_REPEATERS) as $field) {
                    data_set($state, $field, $get($field));
                }
                $filledState = AboutPageTranslationHelper::fillMissingFromEnglish($state);
                foreach (AboutPageTranslationHelper::LOCALIZED_FIELDS as $field) {
                    $set($field, data_get($filledState, $field));
                }
                foreach (array_keys(AboutPageTranslationHelper::LOCALIZED_REPEATERS) as $field) {
                    $set($field, data_get($filledState, $field));
                }
                Notification::make()->title('Missing translations filled from English')->success()->send();
            });
    }

    private static function readinessSummary(Get $get): string
    {
        $missing = AboutPageReadiness::missingItemsFromState(self::pageState($get));

        return $missing === [] ? 'Ready to publish. All required content for enabled sections is complete.' : 'Still needed: '.implode(', ', $missing).'.';
    }

    private static function pageState(Get $get): array
    {
        return collect(['section_visibility', 'hero', 'story', 'values_section', 'team_section', 'milestones_section', 'workflow_section', 'profile_section', 'cta', 'seo'])
            ->mapWithKeys(fn (string $field): array => [$field => $get($field)])->all();
    }

    private static function teamPreviewData(): array
    {
        $total = TeamMember::query()->count();
        $active = TeamMember::query()->active()->count();
        $sample = TeamMember::query()->where('is_sample', true)->count();

        return [
            'records' => TeamMember::query()->ordered()->limit(5)->get()->map(fn (TeamMember $member): array => [
                'name' => $member->name,
                'initials' => Str::of($member->name)->explode(' ')->filter()->take(2)->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))->implode(''),
                'role' => data_get($member->role, 'us') ?: data_get($member->role, 'id'),
                'category' => match ($member->category) {
                    'leadership' => 'Founder & leadership',
                    'booking' => 'Booking & communication',
                    'operations' => 'Trip operations',
                    'field' => 'Field partners',
                    default => Str::headline((string) $member->category),
                },
                'portraitUrl' => $member->portrait ? url(PublicSite::assetPath($member->portrait)) : null,
                'isActive' => (bool) $member->is_active,
                'isSample' => (bool) $member->is_sample,
                'editUrl' => TeamMemberResource::getUrl('edit', ['record' => $member]),
            ]),
            'activeCount' => $active,
            'inactiveCount' => $total - $active,
            'sampleCount' => $sample,
            'remainingCount' => max(0, $total - 5),
            'createUrl' => TeamMemberResource::getUrl('create'),
            'manageUrl' => TeamMemberResource::getUrl('index'),
        ];
    }

    private static function milestonePreviewData(): array
    {
        $total = CompanyMilestone::query()->count();
        $active = CompanyMilestone::query()->active()->count();
        $sample = CompanyMilestone::query()->where('is_sample', true)->count();

        return [
            'records' => CompanyMilestone::query()->ordered()->limit(5)->get()->map(fn (CompanyMilestone $milestone): array => [
                'period' => data_get($milestone->period, 'us') ?: data_get($milestone->period, 'id'),
                'title' => data_get($milestone->title, 'us') ?: data_get($milestone->title, 'id'),
                'isActive' => (bool) $milestone->is_active,
                'isSample' => (bool) $milestone->is_sample,
                'editUrl' => CompanyMilestoneResource::getUrl('edit', ['record' => $milestone]),
            ]),
            'activeCount' => $active,
            'inactiveCount' => $total - $active,
            'sampleCount' => $sample,
            'remainingCount' => max(0, $total - 5),
            'createUrl' => CompanyMilestoneResource::getUrl('create'),
            'manageUrl' => CompanyMilestoneResource::getUrl('index'),
        ];
    }

    private static function iconOptions(): array
    {
        return ['compass' => 'Compass', 'map-pin' => 'Map pin', 'users' => 'People', 'heart' => 'Heart', 'circle-check' => 'Check', 'message-circle' => 'Message', 'search' => 'Search', 'headphones' => 'Support'];
    }
}
