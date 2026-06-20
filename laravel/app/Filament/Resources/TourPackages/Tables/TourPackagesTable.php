<?php

namespace App\Filament\Resources\TourPackages\Tables;

use App\Filament\Support\TourPackageReadiness;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount('itineraryItems'))
            ->columns([
                TextColumn::make('title.us')->label('Title')->searchable(),
                TextColumn::make('destination.name')->label('Destination')->searchable()->sortable(),
                TextColumn::make('readiness_status')
                    ->label('Readiness')
                    ->badge()
                    ->state(fn ($record): string => TourPackageReadiness::status($record))
                    ->color(fn ($record): string => TourPackageReadiness::color($record)),
                TextColumn::make('readiness_summary')
                    ->label('Needs')
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
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('destination_id')->relationship('destination', 'name')->label('Destination'),
                TernaryFilter::make('is_featured'),
                TernaryFilter::make('is_active'),
                Filter::make('needs_attention')
                    ->label('Needs attention')
                    ->query(fn (Builder $query): Builder => $query->where(fn (Builder $query) => TourPackageReadiness::applyNeedsAttention($query))),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('view_site')
                    ->label('View on site')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record): string => route('routes.show', $record->slug))
                    ->visible(fn ($record): bool => (bool) $record->is_active)
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([]);
    }
}
