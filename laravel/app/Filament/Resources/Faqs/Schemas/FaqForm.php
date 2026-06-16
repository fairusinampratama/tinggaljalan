<?php

namespace App\Filament\Resources\Faqs\Schemas;

use App\Filament\Support\AdminForm;
use App\Models\TourPackage;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('FAQ placement')
                    ->schema([
                        Select::make('placement')
                            ->options([
                                'general' => 'General',
                                'homepage' => 'Homepage',
                                'booking' => 'Booking',
                                'package' => 'Package',
                                'destination' => 'Destination',
                            ])
                            ->default('general')
                            ->required(),
                        Select::make('tour_package_id')
                            ->label('Tour package')
                            ->relationship('tourPackage', 'slug')
                            ->getOptionLabelFromRecordUsing(fn (TourPackage $record): string => $record->title['us'] ?? $record->slug)
                            ->searchable()
                            ->preload(),
                        Select::make('destination_id')->relationship('destination', 'name')->searchable()->preload(),
                        TextInput::make('sort_order')->numeric()->default(0),
                        Toggle::make('is_active')->default(true),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                AdminForm::localized('question', 'Question', required: true),
                AdminForm::localized('answer', 'Answer', required: true, textarea: true),
            ]);
    }
}
