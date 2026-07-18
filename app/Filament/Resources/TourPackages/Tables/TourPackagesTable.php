<?php

namespace App\Filament\Resources\TourPackages\Tables;

use App\Filament\Support\TourPackageReadiness;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TourPackagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['itineraryItems', 'priceTiers']))
            ->columns([
                TextColumn::make('title.us')->label('Title')->searchable(),
                TextColumn::make('destination.name')->label('Destination')->searchable()->sortable(),
                TextColumn::make('readiness_status')
                    ->label('Content status')
                    ->badge()
                    ->state(fn ($record): string => TourPackageReadiness::status($record))
                    ->color(fn ($record): string => TourPackageReadiness::color($record)),
                TextColumn::make('readiness_summary')
                    ->label('Missing information')
                    ->state(fn ($record): string => TourPackageReadiness::summary($record))
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('slug')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('duration')->searchable(),
                TextColumn::make('base_price_idr')->money('IDR')->sortable(),
                TextColumn::make('base_price_usd')->money('USD')->sortable(),
                TextColumn::make('review_count')->numeric()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sort_order')->numeric()->sortable()->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_featured')->boolean(),
                IconColumn::make('is_active')->label('Shown publicly')->boolean(),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('destination_id')->relationship('destination', 'name')->label('Destination'),
                TernaryFilter::make('is_featured'),
                TernaryFilter::make('is_active'),
                Filter::make('incomplete_content')
                    ->label('Incomplete content')
                    ->query(fn (Builder $query): Builder => $query->where(fn (Builder $query) => TourPackageReadiness::applyIncomplete($query))),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('view_site')
                    ->label('View on site')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record): string => route('routes.show', $record->slug))
                    ->visible(fn ($record): bool => (bool) $record->is_active)
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->toolbarActions([]);
    }
}
