<?php

namespace App\Filament\Resources\PackageAvailabilities\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PackageAvailabilityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('tourPackage.title')
                    ->label('Tour package')
                    ->placeholder('-'),
                TextEntry::make('destination.name')
                    ->label('Destination')
                    ->placeholder('-'),
                TextEntry::make('date')
                    ->date(),
                TextEntry::make('status'),
                TextEntry::make('seats_left')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('reason')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
