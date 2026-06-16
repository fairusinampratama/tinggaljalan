<?php

namespace App\Filament\Resources\Reviews\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ReviewInfolist
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
                TextEntry::make('name'),
                TextEntry::make('origin')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('rating')
                    ->numeric(),
                TextEntry::make('review_count')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('source')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('text')
                    ->columnSpanFull(),
                IconEntry::make('is_featured')
                    ->boolean(),
                IconEntry::make('is_active')
                    ->boolean(),
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
