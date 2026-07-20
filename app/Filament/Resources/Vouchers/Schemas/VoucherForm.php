<?php

namespace App\Filament\Resources\Vouchers\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class VoucherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Voucher details')
                    ->schema([
                        TextInput::make('code')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->mutateStateForValidationUsing(fn (?string $state): string => strtoupper(trim((string) $state)))
                            ->dehydrateStateUsing(fn (?string $state): string => strtoupper(trim((string) $state)))
                            ->helperText('The code customers enter at checkout (e.g., \'TJDISCOUNT\').'),
                        TextInput::make('label')
                            ->required()
                            ->maxLength(255)
                            ->helperText('A descriptive label for internal use (e.g., \'Summer Promo 10%\').'),
                        Select::make('discount_type')
                            ->options([
                                'percent' => 'Percent',
                                'fixed' => 'Fixed amount',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                if ($state === 'percent') {
                                    $set('currency', null);
                                }
                            })
                            ->helperText('Choose \'Percent\' for a percentage reduction or \'Fixed amount\' for a solid cash discount.'),
                        TextInput::make('discount_value')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->maxValue(fn (Get $get): ?int => $get('discount_type') === 'percent' ? 100 : null)
                            ->helperText('The numeric discount (e.g., \'10\' for 10% or \'$10\').'),
                        Select::make('currency')
                            ->options([
                                'IDR' => 'IDR',
                                'USD' => 'USD',
                            ])
                            ->required(fn (Get $get): bool => $get('discount_type') === 'fixed')
                            ->visible(fn (Get $get): bool => $get('discount_type') === 'fixed')
                            ->live()
                            ->afterStateUpdated(fn (?string $state, Set $set) => $set('allowed_currencies', filled($state) ? [$state] : []))
                            ->helperText('For fixed discounts, define which currency this amount is in.'),
                        TextInput::make('usage_limit')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Maximum number of bookings allowed to use this voucher. Leave blank for unlimited.'),
                        DateTimePicker::make('starts_at')
                            ->helperText('The date and time this voucher becomes active.'),
                        DateTimePicker::make('ends_at')
                            ->after('starts_at')
                            ->helperText('The expiration date and time of this voucher.'),
                        Toggle::make('is_active')
                            ->required()
                            ->default(true)
                            ->helperText('Toggle to enable or disable the voucher globally.'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Section::make('Currency availability')
                    ->description('Choose which booking currencies can use this voucher.')
                    ->schema([
                        CheckboxList::make('allowed_currencies')
                            ->label('Allowed currencies')
                            ->options([
                                'IDR' => 'IDR - Indonesian Rupiah',
                                'USD' => 'USD - US Dollar',
                            ])
                            ->required(fn (Get $get): bool => $get('discount_type') === 'percent')
                            ->minItems(fn (Get $get): int => $get('discount_type') === 'percent' ? 1 : 0)
                            ->visible(fn (Get $get): bool => $get('discount_type') === 'percent')
                            ->bulkToggleable()
                            ->columns(2)
                            ->helperText('Voucher is valid only if the booking uses one of the selected currencies.'),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
