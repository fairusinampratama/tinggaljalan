<?php

namespace App\Filament\Resources\PackageAvailabilities\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PackageAvailabilityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Availability')
                    ->schema([
                        Select::make('tour_package_id')->relationship('tourPackage', 'slug')->searchable()->preload(),
                        Select::make('destination_id')->relationship('destination', 'name')->searchable()->preload(),
                        DatePicker::make('date')->required(),
                        Select::make('status')
                            ->options([
                                'available' => 'Available',
                                'limited' => 'Limited',
                                'booked' => 'Booked',
                                'blocked' => 'Blocked',
                            ])
                            ->default('available')
                            ->required(),
                        TextInput::make('seats_left')->numeric()->minValue(0),
                        TextInput::make('reason')->maxLength(255),
                        Textarea::make('notes')->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }
}
