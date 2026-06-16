<?php

namespace App\Filament\Resources\Faqs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class FaqsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question.us')->label('Question')->limit(60)->searchable(),
                TextColumn::make('placement')->badge()->searchable(),
                TextColumn::make('tourPackage.slug')->label('Package')->searchable(),
                TextColumn::make('destination.name')->searchable(),
                TextColumn::make('sort_order')->numeric()->sortable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('placement')->options([
                    'general' => 'General',
                    'homepage' => 'Homepage',
                    'booking' => 'Booking',
                    'package' => 'Package',
                    'destination' => 'Destination',
                ]),
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
