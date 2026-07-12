<?php

namespace App\Filament\Resources\PackageAvailabilities\Schemas;

use App\Models\PackageAvailability;
use App\Models\TourPackage;
use App\Support\PublicSite;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class PackageAvailabilityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Availability rule')
                    ->description('Specific package rules override destination-wide rules on the same date.')
                    ->schema([
                        Radio::make('scope_type')
                            ->label('Applies to')
                            ->options([
                                'destination' => 'Destination-wide',
                                'package' => 'Specific package',
                            ])
                            ->descriptions([
                                'destination' => 'Apply this date rule to every package in one destination.',
                                'package' => 'Override the destination rule for one package on this date.',
                            ])
                            ->default('destination')
                            ->required()
                            ->live()
                            ->dehydrated(false)
                            ->afterStateHydrated(fn (Set $set, ?PackageAvailability $record) => $set(
                                'scope_type',
                                filled($record?->tour_package_id) ? 'package' : 'destination',
                            ))
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                if ($state === 'package') {
                                    $set('destination_id', null);
                                } else {
                                    $set('tour_package_id', null);
                                }
                            })
                            ->columnSpanFull(),
                        Select::make('destination_id')
                            ->label('Destination')
                            ->relationship('destination', 'name')
                            ->searchable()
                            ->preload()
                            ->required(fn (Get $get): bool => $get('scope_type') === 'destination')
                            ->visible(fn (Get $get): bool => $get('scope_type') === 'destination')
                            ->helperText('Choose the destination to apply this rule to all of its packages.'),
                        Select::make('tour_package_id')
                            ->label('Tour package')
                            ->options(fn (): array => TourPackage::query()
                                ->ordered()
                                ->get()
                                ->mapWithKeys(fn (TourPackage $package): array => [
                                    $package->id => PublicSite::localized($package->title, 'us', $package->slug),
                                ])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required(fn (Get $get): bool => $get('scope_type') === 'package')
                            ->visible(fn (Get $get): bool => $get('scope_type') === 'package')
                            ->helperText('Select the specific tour package to override the destination-wide rule.'),
                        \Filament\Schemas\Components\Grid::make(3)
                            ->schema([
                                DatePicker::make('date')
                                    ->label('Start date')
                                    ->required()
                                    ->native(false)
                                    ->helperText('Select the date when this availability rule begins.'),
                                DatePicker::make('end_date')
                                    ->label('End date')
                                    ->required(fn (Get $get): bool => ! $get('is_open_ended'))
                                    ->visible(fn (Get $get): bool => ! $get('is_open_ended'))
                                    ->afterOrEqual('date')
                                    ->native(false)
                                    ->helperText('The rule remains active until this end date (inclusive).'),
                                \Filament\Forms\Components\Toggle::make('is_open_ended')
                                    ->label('Open-ended')
                                    ->default(false)
                                    ->live()
                                    ->afterStateUpdated(function (bool $state, Set $set): void {
                                        if ($state) {
                                            $set('end_date', null);
                                        }
                                    })
                                    ->helperText('If enabled, this rule applies indefinitely from the start date (hides end date).'),
                            ])
                            ->columnSpanFull(),
                        Select::make('status')
                            ->options([
                                'available' => 'Available',
                                'limited' => 'Limited capacity',
                                'booked' => 'Fully booked',
                                'blocked' => 'Blocked',
                            ])
                            ->helperText('Booked and blocked dates stop checkout. Limited dates show a warning but still accept requests.')
                            ->default('available')
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (?string $state, Set $set) => $state === 'limited' ? null : $set('seats_left', null)),
                        TextInput::make('seats_left')
                            ->label('Seats left')
                            ->numeric()
                            ->minValue(1)
                            ->required(fn (Get $get): bool => $get('status') === 'limited')
                            ->visible(fn (Get $get): bool => $get('status') === 'limited')
                            ->helperText('Requests above this capacity are allowed but clearly flagged for manual confirmation.'),
                        TextInput::make('reason')
                            ->maxLength(255)
                            ->helperText('Short customer-facing explanation, such as vehicle capacity or a local closure.')
                            ->columnSpan(2),
                        Textarea::make('notes')
                            ->helperText('Internal operational notes. These are not shown to customers.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}