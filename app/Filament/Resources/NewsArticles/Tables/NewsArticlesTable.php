<?php

namespace App\Filament\Resources\NewsArticles\Tables;

use App\Filament\Support\NewsArticleReadiness;
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

class NewsArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title.us')->label('Title')->searchable(),
                TextColumn::make('articleCategory.slug')->label('Category')->sortable(),
                TextColumn::make('destination.name')->searchable(),
                TextColumn::make('readiness_status')
                    ->label('Content status')
                    ->badge()
                    ->state(fn ($record): string => NewsArticleReadiness::status($record))
                    ->color(fn ($record): string => NewsArticleReadiness::color($record)),
                TextColumn::make('readiness_summary')
                    ->label('Missing information')
                    ->state(fn ($record): string => NewsArticleReadiness::summary($record))
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('slug')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->label('Publication status')
                    ->color(fn (?string $state): string => match ($state) {
                        'published' => 'success',
                        'draft' => 'gray',
                        default => 'warning',
                    })
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('published_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_featured')->boolean(),
            ])
            ->filters([
                SelectFilter::make('status')->options(['draft' => 'Draft', 'published' => 'Published']),
                SelectFilter::make('article_category_id')->relationship('articleCategory', 'slug')->label('Category'),
                SelectFilter::make('destination_id')->relationship('destination', 'name')->label('Destination'),
                TernaryFilter::make('is_featured'),
                Filter::make('incomplete_content')
                    ->label('Incomplete content')
                    ->query(fn (Builder $query): Builder => $query->where(fn (Builder $query) => NewsArticleReadiness::applyIncomplete($query))),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('view_site')
                    ->label('View on site')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record): string => route('news.show', $record->slug))
                    ->visible(fn ($record): bool => $record->status === 'published')
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
