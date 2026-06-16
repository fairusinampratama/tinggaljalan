<?php

namespace App\Filament\Resources\TourPackages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TourPackagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title.us')->label('Title')->searchable(),
                TextColumn::make('destination.name')->searchable()->sortable(),
                TextColumn::make('slug')->searchable(),
                TextColumn::make('duration')->searchable(),
                TextColumn::make('base_price_idr')->money('IDR')->sortable(),
                TextColumn::make('base_price_usd')->money('USD')->sortable(),
                TextColumn::make('review_count')->numeric()->sortable(),
                TextColumn::make('sort_order')->numeric()->sortable(),
                IconColumn::make('is_featured')->boolean(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('destination_id')->relationship('destination', 'name')->label('Destination'),
                TernaryFilter::make('is_featured'),
                TernaryFilter::make('is_active'),
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
