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
use Filament\Tables\Columns\IconColumn;
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
                TextColumn::make('destination.name')
                    ->label('Destination')
                    ->placeholder('-')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('tourPackage.title')
                    ->label('Tour Package')
                    ->formatStateUsing(fn ($record) => $record->tourPackage ? PublicSite::localized($record->tourPackage->title, 'us', $record->tourPackage->slug) : '-')
                    ->placeholder('-')
                    ->sortable()
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas('tourPackage', fn (Builder $query): Builder => $query->where('title->us', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%")))
                    ->toggleable(),
                TextColumn::make('date')
                    ->label('Start Date')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('End Date')
                    ->placeholder('-')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_open_ended')
                    ->label('Open Ended')
                    ->boolean()
                    ->toggleable(),
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
                    ->toggleable(),
                TextColumn::make('notes')
                    ->placeholder('-')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
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