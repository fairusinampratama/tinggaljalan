<?php

namespace App\Filament\Resources\TourPackages\Schemas;

use App\Filament\Support\AdminForm;
use App\Filament\Support\TourPackageReadiness;
use App\Filament\Support\TourPackageTranslationHelper;
use App\Models\PackagePriceTier;
use App\Models\TourPackage;
use App\Support\RouteFilterOptions;
use Filament\Actions\Action;
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
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

class TourPackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Basic info')
                        ->description('Name, destination, URL, and cover image.')
                        ->schema(self::basicInfoSchema())
                        ->columns(3),
                    Step::make('Homepage & listing')
                        ->description('Card copy, prices, review proof, and publishing controls.')
                        ->schema(self::homepageListingSchema())
                        ->columns(2),
                    Step::make('Detail page content')
                        ->description('Main selling copy shown after customers open the route.')
                        ->schema(self::detailContentSchema())
                        ->columns(2),
                    Step::make('Itinerary & add-ons')
                        ->description('Trip flow and optional extras.')
                        ->schema(self::itinerarySchema()),
                    Step::make('Media & logistics')
                        ->description('Gallery, pickup context, and trip operation details.')
                        ->schema(self::mediaLogisticsSchema())
                        ->columns(3),
                    Step::make('Optional translations')
                        ->description('Leave empty to use English automatically.')
                        ->schema(self::translationSchema())
                        ->columns(2),
                    Step::make('Publishing & Advanced')
                        ->description('Publishing toggles, policies, testimonials, and filters.')
                        ->schema(self::advancedSchema())
                        ->columns(3),
                ])
                    ->skippable()
                    ->persistStepInQueryString('tour-package-step')
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<int, mixed>
     */
    private static function basicInfoSchema(): array
    {
        return [
            Select::make('destination_id')
                ->relationship('destination', 'name')
                ->searchable()
                ->preload()
                ->helperText('The destination used for filters, grouping, and related route suggestions.')
                ->required(),
            AdminForm::primaryLocalizedField('title', 'Title', required: true)
                ->helperText('Main route name shown on cards, detail pages, booking forms, and SEO titles.'),
            TextInput::make('slug')
                ->helperText('URL text for this route, for example bromo-sunrise. Keep it lowercase with hyphens.')
                ->required()
                ->dehydrateStateUsing(fn ($state): string => trim((string) $state))
                ->maxLength(255),
            TextInput::make('duration')
                ->helperText('Short trip length shown to customers, for example 1 day or 2D1N.')
                ->maxLength(255),
            AdminForm::primaryLocalizedField('difficulty', 'Difficulty')
                ->helperText('Simple customer-facing effort level, for example Easy, Moderate, or Challenging.'),
            AdminForm::imageUpload('cover_image', 'Cover image', 'admin/packages/covers')
                ->helperText('Main image used on route cards, detail hero, and social previews.')
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function homepageListingSchema(): array
    {
        return [
            Fieldset::make('Per-person pricing')
                ->schema([
                    Select::make('pricing_mode')
                        ->label('Pricing method')
                        ->options([
                            'flat' => 'One price per person',
                            'tiered' => 'Tiered by number of travelers',
                        ])
                        ->default('flat')
                        ->required()
                        ->live()
                        ->helperText('Tiered pricing lowers the per-person rate for configured group-size ranges.'),
                    Grid::make(2)
                        ->schema([
                            TextInput::make('base_price_idr')
                                ->label('Local price per person')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('IDR')
                                ->required(fn (Get $get): bool => ($get('pricing_mode') ?? 'flat') === 'flat'),
                            TextInput::make('base_price_usd')
                                ->label('International price per person')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('USD')
                                ->required(fn (Get $get): bool => ($get('pricing_mode') ?? 'flat') === 'flat'),
                        ])
                        ->visible(fn (Get $get): bool => ($get('pricing_mode') ?? 'flat') === 'flat'),
                    Repeater::make('priceTiers')
                        ->relationship()
                        ->label('Traveler price tiers')
                        ->schema([
                            TextInput::make('min_pax')->label('From travelers')->numeric()->minValue(1)->required(),
                            TextInput::make('max_pax')->label('To travelers')->numeric()->minValue(1)->placeholder('∞'),
                            TextInput::make('price_idr')->label('IDR / person')->numeric()->minValue(1)->prefix('IDR')->required(),
                            TextInput::make('price_usd')->label('USD / person')->numeric()->minValue(1)->prefix('USD')->required(),
                            TextInput::make('sort_order')->hidden()->default(0),
                        ])
                        ->columns(4)
                        ->defaultItems(1)
                        ->reorderable()
                        ->orderColumn('sort_order')
                        ->rules([
                            fn () => function (string $attribute, mixed $value, \Closure $fail): void {
                                if ($message = PackagePriceTier::validateRanges((array) $value)) {
                                    $fail($message);
                                }
                            },
                        ])
                        ->helperText('Start at 1 and keep every range contiguous. Leave the "To travelers" field empty on your final tier to create an open-ended limit (e.g., 5+ travelers). If you set a final limit, larger groups will be asked to request a custom quote.')
                        ->visible(fn (Get $get): bool => $get('pricing_mode') === 'tiered')
                        ->required(fn (Get $get): bool => $get('pricing_mode') === 'tiered')
                        ->columnSpanFull(),
                ])
                ->columns(1)
                ->columnSpanFull(),
            Fieldset::make('Review display')
                ->schema([
                    TextInput::make('rating')
                        ->helperText('Displayed star rating. Use a value from 0 to 5.')
                        ->numeric()
                        ->step('0.01')
                        ->minValue(0)
                        ->maxValue(5),
                    TextInput::make('review_count')->required()
                        ->label('Review count')
                        ->helperText('Displayed number of reviews. This is public-facing social proof.')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                ])
                ->columns([
                    'default' => 1,
                    'md' => 2,
                ])
                ->columnSpanFull(),
            Fieldset::make('Publishing')
                ->schema([
                    TextInput::make('sort_order')->required()
                        ->label('Sort order')
                        ->helperText('Lower numbers appear first.')
                        ->required()
                        ->numeric()
                        ->default(0),
                    Toggle::make('is_featured')->required()
                        ->label('Show on home page')
                        ->helperText('Adds this route to the main home page featured routes.')
                        ->required(),
                    Toggle::make('is_active')->required()
                        ->label('Show publicly')
                        ->helperText('Publishes this route after all required selling information is complete.')
                        ->rules([
                            fn (Get $get): \Closure => function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                if (! $value) {
                                    return;
                                }

                                $missing = TourPackageReadiness::missingItemsFromState([
                                    'destination_id' => $get('destination_id'),
                                    'title' => ['us' => $get('title.us')],
                                    'slug' => $get('slug'),
                                    'cover_image' => $get('cover_image'),
                                    'duration' => $get('duration'),
                                    'pricing_mode' => $get('pricing_mode'),
                                    'base_price_idr' => $get('base_price_idr'),
                                    'base_price_usd' => $get('base_price_usd'),
                                    'priceTiers' => $get('priceTiers'),
                                    'itineraryItems' => $get('itineraryItems'),
                                    'highlights' => $get('highlights'),
                                    'includes' => $get('includes'),
                                ]);

                                if ($missing !== []) {
                                    $fail('Cannot show publicly. Complete the following: '.implode(', ', $missing).'.');
                                }
                            },
                        ])
                        ->required(),
                ])
                ->columns([
                    'default' => 1,
                    'md' => 3,
                ])
                ->columnSpanFull(),
            AdminForm::primaryLocalizedField('category', 'Category')
                ->helperText('Broad route type shown in route cards and grouping, for example Sunrise tour or Family trip.'),
            AdminForm::primaryLocalizedField('tag', 'Tag')
                ->helperText('Short badge text shown on route cards. Keep it brief.'),
            AdminForm::primaryLocalizedField('excerpt', 'Excerpt', textarea: true)
                ->helperText('Short summary used on listing cards and search previews.')
                ->columnSpanFull(),
            Textarea::make('price_note')
                ->label('Price note')
                ->helperText('Small pricing disclaimer shown near the price, such as final confirmation or seasonal notes.')
                ->rows(3)
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function detailContentSchema(): array
    {
        return [
            AdminForm::primaryLocalizedField('intro', 'Intro', textarea: true)
                ->helperText('Opening paragraph on the detail page. Explain what the traveler gets.')
                ->columnSpanFull(),
            AdminForm::primaryLocalizedField('best_for', 'Best for', textarea: true)
                ->helperText('Helps customers decide if the route fits them, for example families, sunrise hunters, or first-time visitors.')
                ->columnSpanFull(),
            AdminForm::primaryLocalizedRepeater('highlights', 'Highlights', required: true, textarea: true)
                ->helperText('Main selling points shown high on the detail page. Add one point per row.'),
            AdminForm::primaryLocalizedRepeater('includes', 'Includes', required: true)
                ->helperText('What is included in the package price, one item per row.'),
            AdminForm::primaryLocalizedRepeater('excludes', 'Excludes')
                ->helperText('What travelers must pay or arrange separately.'),
            AdminForm::primaryLocalizedRepeater('notes', 'Notes', textarea: true)
                ->helperText('Operational notes that help set expectations before booking.'),
            AdminForm::primaryLocalizedRepeater('details', 'Details', textarea: true)
                ->helperText('Extra route facts shown in the route details list.'),
            AdminForm::primaryLocalizedRepeater('good_to_know', 'Good to know', textarea: true)
                ->helperText('Practical advice such as clothing, weather, pickup timing, or physical requirements.'),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function itinerarySchema(): array
    {
        return [
            Repeater::make('itineraryItems')
                ->relationship()
                ->schema([
                    TextInput::make('day_number')
                        ->helperText('Use 1 for one-day trips. Use 1, 2, 3 for multi-day routes.')
                        ->numeric()
                        ->default(1)
                        ->required(),
                    TextInput::make('time_label')->maxLength(255)
                        ->helperText('Optional time shown before the activity, for example 03:00 or Morning.'),
                    TextInput::make('sort_order')->required()
                        ->helperText('Lower numbers appear earlier within the same day.')
                        ->numeric()
                        ->default(0)
                        ->required(),
                    TextInput::make('title.us')
                        ->label('Title')
                        ->helperText('Short itinerary step title, for example Sunrise viewpoint.')
                        ->required()
                        ->columnSpanFull(),
                    Textarea::make('description.us')
                        ->label('Description')
                        ->helperText('Optional detail for this itinerary step.')
                        ->columnSpanFull(),
                    Section::make('Optional translations for this item')
                        ->description('Leave empty to use English for this itinerary item.')
                        ->schema([
                            TextInput::make('title.id')->label('Title - Indonesian'),
                            TextInput::make('title.cn')->label('Title - Chinese'),
                            Textarea::make('description.id')->label('Description - Indonesian'),
                            Textarea::make('description.cn')->label('Description - Chinese'),
                        ])
                        ->columns(2)
                        ->collapsed()
                        ->collapsible()
                        ->columnSpanFull(),
                ])
                ->defaultItems(0)
                ->reorderable()
                ->columnSpanFull(),
            Repeater::make('packageAddOns')
                ->label('Package add-ons')
                ->relationship()
                ->helperText('Optional extras for this route only. Each route can have different add-ons and prices.')
                ->schema([
                    TextInput::make('title.us')
                        ->label('Add-on title')
                        ->helperText('Customer-facing name, for example Local guide or Extra pickup stop.')
                        ->required()
                        ->columnSpanFull(),
                    Textarea::make('description.us')
                        ->label('Description')
                        ->helperText('Optional short explanation shown near this add-on.')
                        ->rows(2)
                        ->columnSpanFull(),
                    TextInput::make('price_idr')
                        ->label('Route price IDR')
                        ->helperText('Price for this add-on on this route.')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('IDR'),
                    TextInput::make('price_usd')
                        ->label('Route price USD')
                        ->helperText('USD price for this add-on on this route.')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('USD'),
                    Select::make('pricing_type')
                        ->options([
                            'per_booking' => 'Per booking',
                            'per_pax' => 'Per guest',
                        ])
                        ->default('per_booking')
                        ->required(),
                    TextInput::make('sort_order')->required()
                        ->helperText('Lower numbers appear first.')
                        ->numeric()
                        ->default(0)
                        ->required(),
                    Toggle::make('is_active')->required()
                        ->label('Show this add-on')
                        ->helperText('Hidden add-ons are not shown publicly or counted in booking totals.')
                        ->default(true),
                    Section::make('Optional translations for this add-on')
                        ->description('Leave empty to use English for this add-on.')
                        ->schema([
                            TextInput::make('title.id')->label('Title - Indonesian'),
                            TextInput::make('title.cn')->label('Title - Chinese'),
                            Textarea::make('description.id')->label('Description - Indonesian')->rows(2),
                            Textarea::make('description.cn')->label('Description - Chinese')->rows(2),
                        ])
                        ->columns(2)
                        ->collapsed()
                        ->collapsible()
                        ->columnSpanFull(),
                ])
                ->columns([
                    'default' => 1,
                    'md' => 2,
                ])
                ->defaultItems(0)
                ->reorderable()
                ->reorderableWithButtons()
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function mediaLogisticsSchema(): array
    {
        return [
            AdminForm::galleryUpload('gallery', 'Gallery images', 'admin/packages/gallery')
                ->minFiles(2)
                ->maxFiles(10)
                ->helperText('Optional. Upload 2-10 route-specific images. Drag to reorder; the first image appears largest.')
                ->columnSpanFull(),
            AdminForm::primaryLocalizedField('cover_alt', 'Cover alt text')
                ->helperText('Short image description for accessibility and SEO.'),
            AdminForm::pickupAreasRepeater(),
            AdminForm::primaryLocalizedField('pickup_label', 'Pickup label')
                ->helperText('Short public summary shown on route cards, detail meta pills, and booking sidebar. Example: Hotel pickup included.'),
            AdminForm::primaryLocalizedField('group_type', 'Group type')
                ->helperText('Describe the operating model, not only the vehicle. Example: Private trip or Shared jeep.'),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function translationSchema(): array
    {
        return [
            Section::make('Optional page translations')
                ->description('Leave these empty unless you need custom Indonesian or Chinese text. Empty translations use English automatically.')
                ->afterHeader([
                    self::copyEnglishTranslationsAction(),
                ])
                ->schema([
                    ...AdminForm::translationFields('title', 'Title'),
                    ...AdminForm::translationFields('category', 'Category'),
                    ...AdminForm::translationFields('tag', 'Tag'),
                    ...AdminForm::translationFields('excerpt', 'Excerpt', textarea: true),
                    ...AdminForm::translationFields('intro', 'Intro', textarea: true),
                    ...AdminForm::translationFields('best_for', 'Best for', textarea: true),
                    ...AdminForm::translationFields('difficulty', 'Difficulty'),
                    ...AdminForm::translationFields('cover_alt', 'Cover alt text'),
                    ...AdminForm::translationFields('pickup_label', 'Pickup label'),
                    ...AdminForm::translationFields('group_type', 'Group type'),
                    ...AdminForm::translationFields('review_source', 'Review source'),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function advancedSchema(): array
    {
        return [
            Section::make('Route filters')
                ->description('Controls how this package appears in /routes filters and route groupings.')
                ->schema([
                    Select::make('styles')
                        ->helperText('Choose the filter tags customers can use to discover this route.')
                        ->multiple()
                        ->options(fn (?TourPackage $record = null): array => RouteFilterOptions::adminOptions($record?->styles ?? []))
                        ->searchable()
                        ->preload()
                        ->columnSpanFull(),
                ])
                ->columns(3)
                ->columnSpanFull(),
            Section::make('Review proof')
                ->description('Public trust signals shown on the route detail page.')
                ->schema([
                    AdminForm::primaryLocalizedField('review_source', 'Review source')
                        ->helperText('Where the reviews come from, for example Google, Traveloka, or Tripadvisor.')
                        ->columnSpanFull(),
                    AdminForm::testimonialsRepeater(),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Traveler policies')
                ->description('Clear expectations shown near the route detail policy section.')
                ->schema([
                    AdminForm::primaryLocalizedField('policies.cancellation', 'Cancellation policy', textarea: true)
                        ->helperText('Explain cancellation timing, weather changes, and how updates are handled.')
                        ->columnSpanFull(),
                    AdminForm::primaryLocalizedField('policies.confirmation', 'Confirmation policy', textarea: true)
                        ->helperText('Explain confirmation, payment timing, or WhatsApp follow-up.')
                        ->columnSpanFull(),
                    Section::make('Policy translations')
                        ->description('Leave empty to use English automatically.')
                        ->schema([
                            ...AdminForm::translationFields('policies.cancellation', 'Cancellation policy', textarea: true),
                            ...AdminForm::translationFields('policies.confirmation', 'Confirmation policy', textarea: true),
                        ])
                        ->columns(2)
                        ->collapsed()
                        ->collapsible()
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    private static function copyEnglishTranslationsAction(): Action
    {
        return Action::make('copyEnglishToMissingTranslations')
            ->label('Copy English to missing translations')
            ->requiresConfirmation()
            ->modalHeading('Copy English into empty translation fields?')
            ->modalDescription('This only fills empty Indonesian and Chinese fields. Existing translations will not be changed.')
            ->action(function (Get $get, Set $set): void {
                $state = [];

                foreach (TourPackageTranslationHelper::LOCALIZED_FIELDS as $field) {
                    $state[$field] = $get($field);
                }

                foreach (TourPackageTranslationHelper::LOCALIZED_LIST_FIELDS as $field) {
                    $state[$field] = $get($field);
                }

                $state['itineraryItems'] = $get('itineraryItems');
                $filledState = TourPackageTranslationHelper::fillMissingFromEnglish($state);

                foreach ($filledState as $field => $value) {
                    $set($field, $value);
                }

                Notification::make()
                    ->title('Missing translations filled from English')
                    ->success()
                    ->send();
            });
    }
}
