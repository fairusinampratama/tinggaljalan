<?php

namespace App\Filament\Resources\ItineraryItems\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ItineraryItemInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('tourPackage.title')
                    ->label('Tour package'),
                TextEntry::make('day_number')
                    ->numeric(),
                TextEntry::make('time_label')
                    ->placeholder('-'),
                TextEntry::make('title')
                    ->columnSpanFull(),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('sort_order')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
