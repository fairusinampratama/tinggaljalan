<?php

namespace App\Filament\Resources\NewsArticles\Tables;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class NewsArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title.us')->label('Title')->searchable(),
                TextColumn::make('articleCategory.slug')->label('Category')->sortable(),
                TextColumn::make('destination.name')->searchable(),
                TextColumn::make('slug')->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'published' => 'success',
                        'draft' => 'gray',
                        default => 'warning',
                    })
                    ->searchable(),
                TextColumn::make('published_at')->dateTime()->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_featured')->boolean(),
            ])
            ->filters([
                SelectFilter::make('status')->options(['draft' => 'Draft', 'published' => 'Published']),
                SelectFilter::make('article_category_id')->relationship('articleCategory', 'slug')->label('Category'),
                SelectFilter::make('destination_id')->relationship('destination', 'name')->label('Destination'),
                TernaryFilter::make('is_featured'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('view_site')
                    ->label('View on site')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record): string => route('news.show', $record->slug))
                    ->visible(fn ($record): bool => $record->status === 'published' && $record->published_at !== null && $record->published_at->lte(now()))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([]);
    }
}
