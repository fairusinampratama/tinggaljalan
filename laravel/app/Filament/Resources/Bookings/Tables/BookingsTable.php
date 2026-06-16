<?php

namespace App\Filament\Resources\Bookings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking_code')->searchable(),
                TextColumn::make('name')->label('Customer')->searchable(),
                TextColumn::make('tourPackage.slug')->label('Package')->searchable(),
                TextColumn::make('destination.name')->searchable(),
                TextColumn::make('travel_date')->date()->sortable(),
                TextColumn::make('pax')->numeric()->sortable(),
                TextColumn::make('total')->money('IDR')->sortable(),
                TextColumn::make('status')->badge()->searchable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'new' => 'New',
                    'contacted' => 'Contacted',
                    'confirmed' => 'Confirmed',
                    'cancelled' => 'Cancelled',
                    'completed' => 'Completed',
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
