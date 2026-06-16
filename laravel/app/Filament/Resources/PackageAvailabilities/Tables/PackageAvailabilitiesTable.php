<?php

namespace App\Filament\Resources\PackageAvailabilities\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PackageAvailabilitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tourPackage.slug')->label('Package')->searchable(),
                TextColumn::make('destination.name')->searchable(),
                TextColumn::make('date')->date()->sortable(),
                TextColumn::make('status')->badge()->searchable(),
                TextColumn::make('seats_left')->numeric()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'available' => 'Available',
                    'limited' => 'Limited',
                    'booked' => 'Booked',
                    'blocked' => 'Blocked',
                ]),
                SelectFilter::make('destination_id')->relationship('destination', 'name')->label('Destination'),
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
