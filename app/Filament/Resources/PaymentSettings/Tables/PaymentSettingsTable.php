<?php

namespace App\Filament\Resources\PaymentSettings\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('gateway')->badge()->searchable(),
                IconColumn::make('is_enabled')->boolean()->label('Enabled'),
                TextColumn::make('mode')->badge(),
                TextColumn::make('public_label')->label('Public label')->wrap(),
                TextColumn::make('exchange_rate_buffer_percent')->label('FX buffer %'),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}