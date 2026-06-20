<?php

namespace App\Filament\Resources\TourPackages\Schemas;

use App\Filament\Support\TourPackageReadiness;
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
                Section::make('Readiness')
                    ->schema([
                        TextEntry::make('readiness_status')
                            ->label('Status')
                            ->badge()
                            ->state(fn (TourPackage $record): string => TourPackageReadiness::status($record))
                            ->color(fn (TourPackage $record): string => TourPackageReadiness::color($record)),
                        TextEntry::make('readiness_missing')
                            ->label('Missing items')
                            ->state(fn (TourPackage $record): array => TourPackageReadiness::missingItems($record))
                            ->bulleted()
                            ->placeholder('No required gaps found.'),
                    ])
                    ->columns(2)
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
                Section::make('Media and logistics')
                    ->schema([
                        TextEntry::make('gallery_summary')
                            ->label('Gallery images')
                            ->state(fn (TourPackage $record): array => self::assetPaths($record->gallery))
                            ->bulleted()
                            ->placeholder('No gallery images.'),
                        TextEntry::make('cover_alt_us')
                            ->label('Cover alt text')
                            ->state(fn (TourPackage $record): string => PublicSite::localized($record->cover_alt, 'us', '-')),
                        TextEntry::make('pickup_areas_summary')
                            ->label('Pickup areas')
                            ->state(fn (TourPackage $record): array => self::localizedList($record->pickup_areas))
                            ->bulleted()
                            ->placeholder('No pickup areas.'),
                        TextEntry::make('pickup_label_us')
                            ->label('Pickup label')
                            ->state(fn (TourPackage $record): string => PublicSite::localized($record->pickup_label, 'us', '-')),
                        TextEntry::make('group_type_us')
                            ->label('Group type')
                            ->state(fn (TourPackage $record): string => PublicSite::localized($record->group_type, 'us', '-')),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Advanced summary')
                    ->schema([
                        TextEntry::make('styles')
                            ->label('Route filter styles')
                            ->badge()
                            ->placeholder('-'),
                        TextEntry::make('review_source_advanced')
                            ->label('Review source')
                            ->state(fn (TourPackage $record): string => PublicSite::localized($record->review_source, 'us', '-')),
                        TextEntry::make('policy_cancellation')
                            ->label('Cancellation policy')
                            ->state(fn (TourPackage $record): string => PublicSite::localized($record->policies['cancellation'] ?? null, 'us', '-'))
                            ->columnSpanFull(),
                        TextEntry::make('policy_confirmation')
                            ->label('Confirmation policy')
                            ->state(fn (TourPackage $record): string => PublicSite::localized($record->policies['confirmation'] ?? null, 'us', '-'))
                            ->columnSpanFull(),
                        TextEntry::make('testimonials_summary')
                            ->label('Testimonials')
                            ->state(fn (TourPackage $record): array => self::testimonials($record->testimonials))
                            ->bulleted()
                            ->placeholder('No testimonials.'),
                        TextEntry::make('seo_description')
                            ->label('SEO description')
                            ->state(fn (TourPackage $record): string => PublicSite::localized($record->seo['description'] ?? null, 'us', '-'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Advanced metadata')
                    ->schema([
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

    /**
     * @return array<int, string>
     */
    private static function assetPaths(mixed $paths): array
    {
        return collect($paths ?? [])
            ->filter()
            ->map(fn (?string $path): string => PublicSite::assetPath($path))
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private static function testimonials(mixed $items): array
    {
        return collect($items ?? [])
            ->map(function ($item): string {
                if (! is_array($item)) {
                    return '';
                }

                $name = $item['name'] ?? 'Traveler';
                $quote = PublicSite::localized($item['quote'] ?? $item['text'] ?? null, 'us');

                return filled($quote) ? "{$name}: {$quote}" : '';
            })
            ->filter()
            ->values()
            ->all();
    }
}
