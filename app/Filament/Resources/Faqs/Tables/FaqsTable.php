<?php

namespace App\Filament\Resources\Faqs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class FaqsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question.us')
                    ->label('Question')
                    ->limit(80)
                    ->searchable()
                    ->wrap(),
                TextColumn::make('sort_order')->numeric()->sortable(),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->defaultSort('sort_order')
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