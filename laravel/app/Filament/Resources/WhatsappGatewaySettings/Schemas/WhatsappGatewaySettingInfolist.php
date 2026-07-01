<?php

namespace App\Filament\Resources\WhatsappGatewaySettings\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WhatsappGatewaySettingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Gateway')
                ->schema([
                    IconEntry::make('is_enabled')->boolean()->label('Enabled'),
                    TextEntry::make('provider')->badge(),
                    TextEntry::make('api_base_url')->placeholder('-'),
                    TextEntry::make('api_token')->label('API token')->state(fn ($record): string => filled($record->api_token) ? 'Configured' : '-'),
                    TextEntry::make('session_id')->placeholder('-'),
                    TextEntry::make('default_country_code'),
                    TextEntry::make('timeout_seconds')->suffix(' sec'),
                    IconEntry::make('manual_fallback_enabled')->boolean()->label('Manual fallback'),
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