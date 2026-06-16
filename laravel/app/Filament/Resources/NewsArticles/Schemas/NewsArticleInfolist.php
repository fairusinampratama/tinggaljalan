<?php

namespace App\Filament\Resources\NewsArticles\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class NewsArticleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('destination.name')
                    ->label('Destination')
                    ->placeholder('-'),
                TextEntry::make('articleCategory.id')
                    ->label('Article category'),
                TextEntry::make('slug'),
                TextEntry::make('title')
                    ->columnSpanFull(),
                TextEntry::make('excerpt')
                    ->columnSpanFull(),
                ImageEntry::make('cover_image')
                    ->placeholder('-'),
                TextEntry::make('cover_alt')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('tags')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('sections')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('reading_time')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('published_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('content_updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('status'),
                IconEntry::make('is_featured')
                    ->boolean(),
                TextEntry::make('seo')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
