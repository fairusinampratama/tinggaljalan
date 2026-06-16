<?php

namespace App\Filament\Resources\Reviews\Schemas;

use App\Filament\Support\AdminForm;
use App\Models\TourPackage;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Review source')
                    ->schema([
                        Select::make('tour_package_id')
                            ->label('Tour package')
                            ->relationship('tourPackage', 'slug')
                            ->getOptionLabelFromRecordUsing(fn (TourPackage $record): string => $record->title['us'] ?? $record->slug)
                            ->searchable()
                            ->preload(),
                        Select::make('destination_id')->relationship('destination', 'name')->searchable()->preload(),
                        TextInput::make('name')->required()->maxLength(255),
                        TextInput::make('rating')->numeric()->minValue(0)->maxValue(5)->default(5)->required(),
                        TextInput::make('review_count')->numeric()->minValue(0),
                        TextInput::make('sort_order')->numeric()->default(0),
                        Toggle::make('is_featured')->default(false),
                        Toggle::make('is_active')->default(true),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
                AdminForm::localized('origin', 'Origin'),
                AdminForm::localized('source', 'Source'),
                AdminForm::localized('text', 'Review text', required: true, textarea: true),
            ]);
    }
}
