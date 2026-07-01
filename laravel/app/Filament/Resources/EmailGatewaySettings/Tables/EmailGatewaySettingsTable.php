<?php

namespace App\Filament\Resources\EmailGatewaySettings\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmailGatewaySettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_enabled')->boolean()->label('Enabled'),
                TextColumn::make('provider')->badge(),
                TextColumn::make('host')->placeholder('-'),
                TextColumn::make('from_address')->placeholder('-'),
                TextColumn::make('last_test_status')->badge()->placeholder('-'),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->toolbarActions([]);
    }
}