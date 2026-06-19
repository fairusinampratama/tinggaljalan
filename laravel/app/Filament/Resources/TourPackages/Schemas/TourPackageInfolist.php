<?php

namespace App\Filament\Resources\TourPackages\Schemas;

use App\Models\TourPackage;
use App\Support\PublicSite;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class TourPackageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Package summary')
                    ->schema([
                        ImageEntry::make('cover_image')
                            ->label('Cover')
                            ->state(fn (TourPackage $record): string => self::imageUrl($record->cover_image))
                            ->checkFileExistence(false)
                            ->imageHeight('10rem')
                            ->columnSpanFull(),
                        TextEntry::make('title_us')
                            ->label('Title')
                            ->state(fn (TourPackage $record): string => PublicSite::localized($record->title, 'us', $record->slug)),
                        TextEntry::make('destination.name')->label('Destination')->placeholder('-'),
                        TextEntry::make('slug'),
                        TextEntry::make('duration')->placeholder('-'),
                        TextEntry::make('difficulty_us')
                            ->label('Difficulty')
                            ->state(fn (TourPackage $record): string => PublicSite::localized($record->difficulty, 'us', '-')),
                        TextEntry::make('styles')->badge()->placeholder('-'),
                        IconEntry::make('is_active')->label('Active')->boolean(),
                        IconEntry::make('is_featured')->label('Featured')->boolean(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Section::make('Pricing and reviews')
                    ->schema([
                        TextEntry::make('base_price_idr')->money('IDR')->placeholder('-'),
                        TextEntry::make('base_price_usd')->money('USD')->placeholder('-'),
                        TextEntry::make('price_note')->placeholder('-')->columnSpanFull(),
                        TextEntry::make('rating')->numeric(decimalPlaces: 2)->placeholder('-'),
                        TextEntry::make('review_count')->numeric(),
                        TextEntry::make('review_source_us')
                            ->label('Review source')
                            ->state(fn (TourPackage $record): string => PublicSite::localized($record->review_source, 'us', '-')),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Section::make('Public copy')
                    ->schema([
                        TextEntry::make('category_us')
                            ->label('Category')
                            ->state(fn (TourPackage $record): string => PublicSite::localized($record->category, 'us', '-')),
                        TextEntry::make('tag_us')
                            ->label('Tag')
                            ->state(fn (TourPackage $record): string => PublicSite::localized($record->tag, 'us', '-')),
                        TextEntry::make('excerpt_us')
                            ->label('Excerpt')
                            ->state(fn (TourPackage $record): string => PublicSite::localized($record->excerpt, 'us', '-'))
                            ->columnSpanFull(),
                        TextEntry::make('intro_us')
                            ->label('Intro')
                            ->state(fn (TourPackage $record): string => PublicSite::localized($record->intro, 'us', '-'))
                            ->columnSpanFull(),
                        TextEntry::make('best_for_us')
                            ->label('Best for')
                            ->state(fn (TourPackage $record): string => PublicSite::localized($record->best_for, 'us', '-'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Traveler-facing lists')
                    ->schema([
                        TextEntry::make('highlights')
                            ->state(fn (TourPackage $record): array => self::localizedList($record->highlights))
                            ->bulleted()
                            ->placeholder('-'),
                        TextEntry::make('includes')
                            ->state(fn (TourPackage $record): array => self::localizedList($record->includes))
                            ->bulleted()
                            ->placeholder('-'),
                        TextEntry::make('excludes')
                            ->state(fn (TourPackage $record): array => self::localizedList($record->excludes))
                            ->bulleted()
                            ->placeholder('-'),
                        TextEntry::make('good_to_know')
                            ->label('Good to know')
                            ->state(fn (TourPackage $record): array => self::localizedList($record->good_to_know))
                            ->bulleted()
                            ->placeholder('-'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Advanced metadata')
                    ->schema([
                        TextEntry::make('pickup_areas')->placeholder('-')->columnSpanFull(),
                        TextEntry::make('policies')->placeholder('-')->columnSpanFull(),
                        TextEntry::make('testimonials')->placeholder('-')->columnSpanFull(),
                        TextEntry::make('seo')->label('SEO metadata')->placeholder('-')->columnSpanFull(),
                    ])
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
    private static function localizedList(mixed $items): array
    {
        if (is_array($items) && (isset($items['id']) || isset($items['us']) || isset($items['cn'])) && (is_array($items['id'] ?? null) || is_array($items['us'] ?? null) || is_array($items['cn'] ?? null))) {
            $count = max(count($items['id'] ?? []), count($items['us'] ?? []), count($items['cn'] ?? []));

            return collect(range(0, max(0, $count - 1)))
                ->map(fn (int $index): string => $items['us'][$index] ?? $items['id'][$index] ?? $items['cn'][$index] ?? '')
                ->filter()
                ->values()
                ->all();
        }

        return collect($items ?? [])
            ->map(fn ($item): string => is_array($item) ? PublicSite::localized($item, 'us') : (string) $item)
            ->filter()
            ->values()
            ->all();
    }
}
