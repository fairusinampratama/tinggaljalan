<?php

namespace App\Filament\Resources\NotificationSettings\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NotificationSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_enabled')->boolean()->label('Enabled'),
                IconColumn::make('whatsapp_enabled')->boolean()->label('WhatsApp'),
                TextColumn::make('admin_whatsapp_number')->label('Admin WhatsApp')->placeholder('-'),
                IconColumn::make('email_enabled')->boolean()->label('Email'),
                TextColumn::make('admin_email')->label('Admin email')->placeholder('-'),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([]);
    }
}