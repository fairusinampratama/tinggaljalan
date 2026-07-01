<?php

namespace App\Filament\Resources\NewsArticles;

use App\Filament\Resources\NewsArticles\Pages\CreateNewsArticle;
use App\Filament\Resources\NewsArticles\Pages\EditNewsArticle;
use App\Filament\Resources\NewsArticles\Pages\ListNewsArticles;
use App\Filament\Resources\NewsArticles\Schemas\NewsArticleForm;
use App\Filament\Resources\NewsArticles\Tables\NewsArticlesTable;
use App\Models\NewsArticle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class NewsArticleResource extends Resource
{
    protected static ?string $model = NewsArticle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 10;

    public static function getNavigationBadge(): ?string
    {
        $count = \App\Filament\Support\NewsArticleReadiness::applyNeedsAttention(\App\Models\NewsArticle::query())->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return NewsArticleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NewsArticlesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNewsArticles::route('/'),
            'create' => CreateNewsArticle::route('/create'),
            'edit' => EditNewsArticle::route('/{record}/edit'),
        ];
    }
}
