<?php

namespace App\Filament\Resources\TourPackages\Schemas;

use App\Filament\Support\AdminForm;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TourPackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Package overview')
                    ->description('The basics operators need to identify and publish this route.')
                    ->schema([
                        Select::make('destination_id')->relationship('destination', 'name')->searchable()->preload()->required(),
                        TextInput::make('slug')->required()->maxLength(255),
                        TextInput::make('duration')->maxLength(255),
                        AdminForm::imageUpload('cover_image', 'Cover image', 'admin/packages/covers')
                            ->columnSpanFull(),
                        ...AdminForm::localizedFields('title', required: true),
                    ])
                    ->columns(3),
                Section::make('Public page copy')
                    ->description('Localized text used across route cards, detail pages, SEO previews, and booking summaries.')
                    ->schema([
                        ...AdminForm::localizedFields('category'),
                        ...AdminForm::localizedFields('tag'),
                        ...AdminForm::localizedFields('excerpt', textarea: true),
                        ...AdminForm::localizedFields('intro', textarea: true),
                        ...AdminForm::localizedFields('best_for', textarea: true),
                        ...AdminForm::localizedFields('difficulty'),
                        ...AdminForm::localizedFields('cover_alt'),
                    ])
                    ->columns(3)
                    ->collapsible(),
                Section::make('Pricing and status')
                    ->description('Controls public visibility, featured placement, and displayed starting prices.')
                    ->schema([
                        TextInput::make('base_price_idr')->numeric()->prefix('IDR'),
                        TextInput::make('base_price_usd')->numeric()->prefix('USD'),
                        TextInput::make('rating')->numeric()->step('0.01'),
                        TextInput::make('review_count')->required()->numeric()->default(0),
                        TextInput::make('sort_order')->required()->numeric()->default(0),
                        Toggle::make('is_featured')->required(),
                        Toggle::make('is_active')->required(),
                        Textarea::make('price_note')->columnSpanFull(),
                    ])
                    ->columns(4),
                Section::make('Itinerary and add-ons')
                    ->description('What travelers compare before contacting the team.')
                    ->schema([
                        Repeater::make('itineraryItems')
                            ->relationship()
                            ->schema([
                                TextInput::make('day_number')->numeric()->default(1)->required(),
                                TextInput::make('time_label'),
                                TextInput::make('sort_order')->numeric()->default(0)->required(),
                                AdminForm::localized('title', 'Title', required: true),
                                AdminForm::localized('description', 'Description', textarea: true),
                            ])
                            ->defaultItems(0)
                            ->reorderable()
                            ->columnSpanFull(),
                        CheckboxList::make('addOns')
                            ->relationship(
                                name: 'addOns',
                                titleAttribute: 'slug',
                                modifyQueryUsing: fn ($query) => $query->orderBy('slug'),
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->title['us'] ?? $record->slug)
                            ->columns(2)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make('Media and logistics')
                    ->description('Images and pickup context used on public pages and booking forms.')
                    ->schema([
                        AdminForm::imageUpload('gallery', 'Gallery images', 'admin/packages/gallery', multiple: true)
                            ->columnSpanFull(),
                        AdminForm::json('pickup_areas', 'Pickup areas'),
                        ...AdminForm::localizedFields('pickup_label'),
                        ...AdminForm::localizedFields('group_type'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Section::make('Traveler-facing lists')
                    ->description('Structured bullets shown in package detail sections.')
                    ->schema([
                        AdminForm::localizedRepeater('highlights', 'Highlights', textarea: true),
                        AdminForm::localizedRepeater('includes', 'Includes'),
                        AdminForm::localizedRepeater('excludes', 'Excludes'),
                        AdminForm::localizedRepeater('notes', 'Notes', textarea: true),
                        AdminForm::localizedRepeater('details', 'Details', textarea: true),
                        AdminForm::localizedRepeater('good_to_know', 'Good to know', textarea: true),
                    ])
                    ->columnSpanFull()
                    ->collapsible(),
                Section::make('Advanced metadata')
                    ->description('Structured data used by policies, testimonials, route filters, and SEO.')
                    ->schema([
                        AdminForm::json('policies', 'Policies'),
                        AdminForm::json('testimonials', 'Testimonials'),
                        ...AdminForm::localizedFields('review_source'),
                        Select::make('styles')
                            ->multiple()
                            ->options([
                                'recommended' => 'Recommended',
                                'family' => 'Family',
                                'adventure' => 'Adventure',
                                'waterfall' => 'Waterfall',
                                'sunrise' => 'Sunrise',
                                'culture' => 'Culture',
                                'multi-day' => 'Multi-day',
                            ])
                            ->columnSpanFull(),
                        AdminForm::json('seo', 'SEO metadata'),
                    ])
                    ->columns(3)
                    ->columnSpanFull()
                    ->collapsed(),
            ]);
    }
}
