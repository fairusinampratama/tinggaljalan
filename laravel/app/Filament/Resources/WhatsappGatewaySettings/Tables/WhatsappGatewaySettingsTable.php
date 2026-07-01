<?php

namespace App\Filament\Resources\WhatsappGatewaySettings\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WhatsappGatewaySettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_enabled')->boolean()->label('Enabled'),
                TextColumn::make('provider')->badge(),
                TextColumn::make('api_base_url')->placeholder('-'),
                TextColumn::make('session_id')->placeholder('-'),
                IconColumn::make('manual_fallback_enabled')->boolean()->label('Fallback'),
                TextColumn::make('last_test_status')->badge()->placeholder('-'),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->toolbarActions([]);
    }
}