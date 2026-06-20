<?php

namespace App\Filament\Resources\RouteFilters\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RouteFilterInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Filter summary')
                    ->schema([
                        TextEntry::make('label.us')->label('English label'),
                        TextEntry::make('slug'),
                        TextEntry::make('sort_order')->numeric(),
                        IconEntry::make('is_active')->label('Active')->boolean(),
                        TextEntry::make('description.us')
                            ->label('Description')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Translations')
                    ->schema([
                        TextEntry::make('label.id')->label('Indonesian label')->placeholder('-'),
                        TextEntry::make('label.cn')->label('Chinese label')->placeholder('-'),
                        TextEntry::make('description.id')->label('Indonesian description')->placeholder('-'),
                        TextEntry::make('description.cn')->label('Chinese description')->placeholder('-'),
                    ])
                    ->columns(2)
                    ->collapsed()
                    ->collapsible()
                    ->columnSpanFull(),
            ]);
    }
}
