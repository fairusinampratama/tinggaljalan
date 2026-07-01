<?php

namespace App\Filament\Resources\ItineraryItems\Schemas;

use App\Filament\Support\AdminForm;
use App\Models\TourPackage;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ItineraryItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Itinerary placement')
                    ->schema([
                        Select::make('tour_package_id')
                            ->label('Tour package')
                            ->relationship('tourPackage', 'slug')
                            ->getOptionLabelFromRecordUsing(fn (TourPackage $record): string => $record->title['us'] ?? $record->slug)
                            ->required()
                            ->searchable()
                            ->preload(),
                        TextInput::make('day_number')->numeric()->default(1)->required(),
                        TextInput::make('time_label')->maxLength(255),
                        TextInput::make('sort_order')->required()->numeric()->default(0),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
                AdminForm::localized('title', 'Title', required: true),
                AdminForm::localized('description', 'Description', textarea: true),
            ]);
    }
}
