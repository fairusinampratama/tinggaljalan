<?php

namespace App\Filament\Resources\EmailGatewaySettings\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmailGatewaySettingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Gateway')
                ->schema([
                    IconEntry::make('is_enabled')->boolean()->label('Enabled'),
                    TextEntry::make('provider')->badge(),
                    TextEntry::make('host')->placeholder('-'),
                    TextEntry::make('port')->placeholder('-'),
                    TextEntry::make('scheme')->placeholder('-'),
                    TextEntry::make('username')->placeholder('-'),
                    TextEntry::make('password')->label('Password')->state(fn ($record): string => filled($record->password) ? 'Configured' : '-'),
                    TextEntry::make('from_address')->placeholder('-'),
                    TextEntry::make('from_name')->placeholder('-'),
                ])
                ->columns(3)
                ->columnSpanFull(),
            Section::make('Last test')
                ->schema([
                    TextEntry::make('last_tested_at')->dateTime()->placeholder('-'),
                    TextEntry::make('last_test_status')->badge()->placeholder('-'),
                    TextEntry::make('last_test_message')->placeholder('-')->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}