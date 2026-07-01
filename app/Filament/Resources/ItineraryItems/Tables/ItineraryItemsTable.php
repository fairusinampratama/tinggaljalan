<?php

namespace App\Filament\Resources\ItineraryItems\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ItineraryItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title.us')->label('Title')->limit(50)->searchable(),
                TextColumn::make('tourPackage.slug')->label('Package')->searchable(),
                TextColumn::make('day_number')->numeric()->sortable(),
                TextColumn::make('time_label')->searchable(),
                TextColumn::make('sort_order')->numeric()->sortable(),
            ])
            ->filters([
                SelectFilter::make('tour_package_id')->relationship('tourPackage', 'slug')->label('Package'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
