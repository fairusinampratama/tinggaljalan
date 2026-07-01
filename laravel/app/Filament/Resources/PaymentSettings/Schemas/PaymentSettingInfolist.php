<?php

namespace App\Filament\Resources\PaymentSettings\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentSettingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Gateway')
                    ->schema([
                        TextEntry::make('gateway')->badge(),
                        IconEntry::make('is_enabled')->boolean()->label('Enabled'),
                        TextEntry::make('mode')->badge(),
                        TextEntry::make('public_key')->label('Client key')->state(fn ($record): string => filled($record->public_key) ? 'Configured' : 'Using env fallback'),
                        TextEntry::make('secret_key')->label('Server key')->state(fn ($record): string => filled($record->secret_key) ? 'Configured' : 'Using env fallback'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Section::make('Public copy')
                    ->schema([
                        TextEntry::make('public_label'),
                        TextEntry::make('booking_note')->columnSpanFull(),
                        TextEntry::make('usd_note')->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Exchange rate')
                    ->schema([
                        TextEntry::make('exchange_rate_provider')->label('Provider'),
                        TextEntry::make('exchange_rate_buffer_percent')->label('Buffer %'),
                        TextEntry::make('exchange_rate_cache_ttl_hours')->label('Cache TTL hours'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }
}