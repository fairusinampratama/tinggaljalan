<?php

namespace App\Filament\Resources\Vouchers\Schemas;

use App\Filament\Support\AdminForm;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
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
                Section::make('Voucher basics')
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
                        ToggleButtons::make('discount_type')
                            ->label('Discount type')
                            ->options([
                                'percent' => 'Percentage off',
                                'fixed' => 'Fixed amount off',
                            ])
                            ->required()
                            ->default('percent')
                            ->inline()
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                if ($state === 'percent') {
                                    $set('currency', null);
                                }
                            })
                            ->helperText('Choose the voucher menu first. The next section changes based on this choice.')
                            ->columnSpanFull(),
                        TextInput::make('usage_limit')
                            ->label('Usage limit')
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
                Section::make('Homepage promotion')
                    ->description('Control whether this voucher appears in the public Current promotions carousel.')
                    ->schema([
                        Toggle::make('is_public')
                            ->label('Show on homepage')
                            ->default(false)
                            ->helperText('The voucher must also be active, current, available, and valid for the visitor currency.'),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Lower numbers appear first.'),
                        TextInput::make('maximum_discount_idr')
                            ->label('Maximum discount (IDR)')
                            ->numeric()
                            ->minValue(1)
                            ->prefix('Rp')
                            ->helperText('Optional display cap for IDR percentage promotions.'),
                        TextInput::make('maximum_discount_usd')
                            ->label('Maximum discount (USD)')
                            ->numeric()
                            ->minValue(1)
                            ->prefix('$')
                            ->helperText('Optional display cap for USD percentage promotions.'),
                        Select::make('tourPackages')
                            ->label('Eligible tour packages')
                            ->relationship(
                                name: 'tourPackages',
                                titleAttribute: 'slug',
                                modifyQueryUsing: fn ($query) => $query->active()->ordered(),
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->title['us'] ?? $record->slug)
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('Leave empty when the promotion applies to every eligible trip.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                AdminForm::localized('public_title', 'Public promotion title')
                    ->description('Customer-facing card title. English is used as fallback when a translation is empty.'),
                AdminForm::localized('public_description', 'Public promotion description', textarea: true)
                    ->description('A short explanation of the offer. Voucher codes are shown separately.'),
                Section::make('Percentage voucher settings')
                    ->description('Use this menu for codes like BROMO10 that discount a percentage of the booking total.')
                    ->visible(fn (Get $get): bool => $get('discount_type') === 'percent')
                    ->schema([
                        TextInput::make('discount_value')
                            ->label('Percentage off')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->maxValue(100)
                            ->suffix('%')
                            ->helperText('Use 10 for a 10% discount.'),
                        CheckboxList::make('allowed_currencies')
                            ->label('Eligible booking currencies')
                            ->options([
                                'IDR' => 'IDR - local bookings',
                                'USD' => 'USD - international bookings',
                            ])
                            ->required()
                            ->minItems(1)
                            ->bulkToggleable()
                            ->columns(2)
                            ->helperText('Select USD for international bookings, IDR for local bookings, or both for everyone.'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Fixed amount voucher settings')
                    ->description('Use this menu for cash-value vouchers, such as IDR 50,000 off or USD 10 off.')
                    ->visible(fn (Get $get): bool => $get('discount_type') === 'fixed')
                    ->schema([
                        TextInput::make('discount_value')
                            ->label('Fixed discount amount')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->helperText('Enter the cash amount in the selected discount currency.'),
                        Select::make('currency')
                            ->label('Fixed discount currency')
                            ->options([
                                'IDR' => 'IDR',
                                'USD' => 'USD',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (?string $state, Set $set) => $set('allowed_currencies', filled($state) ? [$state] : []))
                            ->helperText('The fixed amount can only apply to bookings using this same currency.'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
