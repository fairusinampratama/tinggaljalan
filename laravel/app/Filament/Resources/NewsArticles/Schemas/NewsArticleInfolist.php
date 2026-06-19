<?php

namespace App\Filament\Resources\NewsArticles\Schemas;

use App\Models\NewsArticle;
use App\Support\PublicSite;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class NewsArticleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Article summary')
                    ->schema([
                        ImageEntry::make('cover_image')
                            ->label('Cover')
                            ->state(fn (NewsArticle $record): string => self::imageUrl($record->cover_image))
                            ->checkFileExistence(false)
                            ->imageHeight('10rem')
                            ->columnSpanFull(),
                        TextEntry::make('title_us')
                            ->label('Title')
                            ->state(fn (NewsArticle $record): string => PublicSite::localized($record->title, 'us', $record->slug)),
                        TextEntry::make('slug'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'published' => 'success',
                                'draft' => 'gray',
                                default => 'warning',
                            }),
                        TextEntry::make('articleCategory.slug')->label('Category')->placeholder('-'),
                        TextEntry::make('destination.name')->label('Destination')->placeholder('-'),
                        IconEntry::make('is_featured')->label('Featured')->boolean(),
                        TextEntry::make('published_at')->dateTime()->placeholder('-'),
                        TextEntry::make('content_updated_at')->label('Content updated')->dateTime()->placeholder('-'),
                        TextEntry::make('reading_time_us')
                            ->label('Reading time')
                            ->state(fn (NewsArticle $record): string => PublicSite::localized($record->reading_time, 'us', '-')),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Section::make('Public copy')
                    ->schema([
                        TextEntry::make('excerpt_us')
                            ->label('Excerpt')
                            ->state(fn (NewsArticle $record): string => PublicSite::localized($record->excerpt, 'us', '-'))
                            ->columnSpanFull(),
                        TextEntry::make('tags')
                            ->badge()
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('sections')
                            ->label('Sections')
                            ->state(fn (NewsArticle $record): array => self::sectionHeadings($record))
                            ->bulleted()
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Advanced metadata')
                    ->schema([
                        TextEntry::make('cover_alt')->label('Cover alt text')->placeholder('-')->columnSpanFull(),
                        TextEntry::make('seo')->label('SEO metadata')->placeholder('-')->columnSpanFull(),
                        TextEntry::make('created_at')->dateTime()->placeholder('-'),
                        TextEntry::make('updated_at')->dateTime()->placeholder('-'),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->collapsed(),
            ]);
    }

    private static function imageUrl(?string $path): string
    {
        $asset = PublicSite::assetPath($path);

        return Str::startsWith($asset, ['http://', 'https://']) ? $asset : url($asset);
    }

    /**
     * @return array<int, string>
     */
    private static function sectionHeadings(NewsArticle $record): array
    {
        return collect($record->sections ?? [])
            ->map(fn (array $section): string => PublicSite::localized($section['heading'] ?? [], 'us'))
            ->filter()
            ->values()
            ->all();
    }
}
