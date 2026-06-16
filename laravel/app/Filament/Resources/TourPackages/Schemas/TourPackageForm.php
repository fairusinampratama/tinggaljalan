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
                Section::make('Package identity')
                    ->schema([
                        Select::make('destination_id')->relationship('destination', 'name')->searchable()->preload()->required(),
                        TextInput::make('slug')->required()->maxLength(255),
                        TextInput::make('duration')->maxLength(255),
                        TextInput::make('cover_image')->label('Cover image path')->maxLength(255),
                    ])
                    ->columns(2),
                AdminForm::localized('title', 'Title', required: true),
                AdminForm::localized('category', 'Category'),
                AdminForm::localized('tag', 'Tag'),
                AdminForm::localized('excerpt', 'Excerpt', textarea: true),
                AdminForm::localized('intro', 'Intro', textarea: true),
                AdminForm::localized('best_for', 'Best for', textarea: true),
                AdminForm::localized('difficulty', 'Difficulty'),
                AdminForm::localized('cover_alt', 'Cover alt text'),
                Section::make('Pricing and status')
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
                AdminForm::json('gallery', 'Gallery image paths'),
                AdminForm::json('pickup_areas', 'Pickup areas'),
                AdminForm::localized('pickup_label', 'Pickup label'),
                AdminForm::localized('group_type', 'Group type'),
                AdminForm::json('highlights', 'Highlights'),
                AdminForm::json('includes', 'Includes'),
                AdminForm::json('excludes', 'Excludes'),
                AdminForm::json('notes', 'Notes'),
                AdminForm::json('details', 'Details'),
                AdminForm::json('good_to_know', 'Good to know'),
                AdminForm::json('policies', 'Policies'),
                AdminForm::json('testimonials', 'Testimonials'),
                AdminForm::localized('review_source', 'Review source'),
                AdminForm::json('styles', 'Styles'),
                AdminForm::json('seo', 'SEO metadata'),
            ]);
    }
}
