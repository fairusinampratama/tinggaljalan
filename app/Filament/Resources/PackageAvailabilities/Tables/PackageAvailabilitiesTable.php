<?php

namespace App\Filament\Resources\PackageAvailabilities\Tables;

use App\Models\PackageAvailability;
use App\Models\TourPackage;
use App\Support\PublicSite;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PackageAvailabilitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['tourPackage.destination', 'destination']))
            ->columns([
                TextColumn::make('scope')
                    ->label('Scope')
                    ->badge()
                    ->state(fn (PackageAvailability $record): string => $record->tour_package_id ? 'Specific package' : 'Destination-wide')
                    ->color(fn (PackageAvailability $record): string => $record->tour_package_id ? 'info' : 'gray'),
                TextColumn::make('subject')
                    ->label('Applies to')
                    ->state(fn (PackageAvailability $record): string => $record->tourPackage
                        ? PublicSite::localized($record->tourPackage->title, 'us', $record->tourPackage->slug)
                        : ($record->destination?->name ?? 'Missing scope'))
                    ->description(fn (PackageAvailability $record): ?string => $record->tourPackage?->destination?->name)
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where(function (Builder $query) use ($search): void {
                        $query
                            ->whereHas('destination', fn (Builder $query): Builder => $query->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('tourPackage', fn (Builder $query): Builder => $query
                                ->where('slug', 'like', "%{$search}%")
                                ->orWhere('title->us', 'like', "%{$search}%"));
                    }))
                    ->wrap(),
                TextColumn::make('date')->date()->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'limited' => 'Limited capacity',
                        'booked' => 'Fully booked',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'available' => 'success',
                        'limited' => 'warning',
                        'booked', 'blocked' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('seats_left')
                    ->label('Seats')
                    ->numeric()
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('reason')
                    ->placeholder('-')
                    ->limit(50)
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('scope')
                    ->options([
                        'destination' => 'Destination-wide',
                        'package' => 'Specific package',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(($data['value'] ?? null) === 'destination', fn (Builder $query): Builder => $query->whereNull('tour_package_id')->whereNotNull('destination_id'))
                        ->when(($data['value'] ?? null) === 'package', fn (Builder $query): Builder => $query->whereNotNull('tour_package_id'))),
                SelectFilter::make('status')->options([
                    'available' => 'Available',
                    'limited' => 'Limited capacity',
                    'booked' => 'Fully booked',
                    'blocked' => 'Blocked',
                ]),
                SelectFilter::make('destination_id')->relationship('destination', 'name')->label('Destination'),
                SelectFilter::make('tour_package_id')
                    ->label('Tour package')
                    ->options(fn (): array => TourPackage::query()->ordered()->get()->mapWithKeys(fn (TourPackage $package): array => [
                        $package->id => PublicSite::localized($package->title, 'us', $package->slug),
                    ])->all()),
                Filter::make('date_range')
                    ->schema([
                        DatePicker::make('from')->native(false),
                        DatePicker::make('until')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date))),
            ])
            ->defaultSort('date')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}