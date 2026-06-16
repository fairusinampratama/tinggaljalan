<?php

namespace App\Filament\Resources\Faqs\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class FaqInfolist
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
                TextEntry::make('question')
                    ->columnSpanFull(),
                TextEntry::make('answer')
                    ->columnSpanFull(),
                TextEntry::make('placement'),
                TextEntry::make('sort_order')
                    ->numeric(),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
