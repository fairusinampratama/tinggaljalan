<?php

namespace App\Filament\Resources\TrustStats\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TrustStatsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title.us')->label('Title')->searchable(),
                TextColumn::make('value.us')->label('Value')->searchable(),
                TextColumn::make('icon_key')->searchable(),
                TextColumn::make('sort_order')->numeric()->sortable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
