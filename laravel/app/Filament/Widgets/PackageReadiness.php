<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\TourPackages\TourPackageResource;
use App\Models\TourPackage;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PackageReadiness extends TableWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 30;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Package readiness')
            ->description('Packages missing public-page essentials before they feel production-ready.')
            ->query(
                TourPackage::query()
                    ->with('destination')
                    ->withCount('itineraryItems')
                    ->where(function ($query) {
                        $query
                            ->where('is_active', false)
                            ->orWhereNull('cover_image')
                            ->orWhere(function ($query) {
                                $query->whereNull('base_price_idr')->whereNull('base_price_usd');
                            })
                            ->orDoesntHave('itineraryItems');
                    })
                    ->latest('updated_at')
                    ->limit(8),
            )
            ->columns([
                TextColumn::make('title.us')
                    ->label('Package')
                    ->searchable(),
                TextColumn::make('destination.name')
                    ->label('Destination')
                    ->placeholder('-'),
                TextColumn::make('readiness')
                    ->label('Needs')
                    ->state(fn (TourPackage $record): string => implode(', ', self::readinessIssues($record))),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->recordActions([
                Action::make('edit')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->url(fn (TourPackage $record): string => TourPackageResource::getUrl('edit', ['record' => $record])),
                Action::make('view_site')
                    ->label('View site')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (TourPackage $record): string => route('routes.show', $record->slug))
                    ->visible(fn (TourPackage $record): bool => (bool) $record->is_active)
                    ->openUrlInNewTab(),
            ])
            ->paginated(false)
            ->emptyStateHeading('Packages look ready')
            ->emptyStateDescription('No active-status, image, price, or itinerary gaps found.');
    }

    /**
     * @return array<int, string>
     */
    private static function readinessIssues(TourPackage $record): array
    {
        return collect([
            $record->is_active ? null : 'Inactive',
            filled($record->cover_image) ? null : 'Cover image',
            ($record->base_price_idr || $record->base_price_usd) ? null : 'Price',
            ((int) ($record->itinerary_items_count ?? 0)) > 0 ? null : 'Itinerary',
        ])
            ->filter()
            ->values()
            ->all();
    }
}
