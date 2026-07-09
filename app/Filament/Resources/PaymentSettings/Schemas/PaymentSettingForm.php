<?php

namespace App\Filament\Resources\PaymentSettings\Schemas;

use App\Models\PaymentSetting;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Gateway configuration')
                    ->description(fn (callable $get) => $get('gateway') === 'midtrans' ? 'Midtrans keys are stored encrypted and never exposed publicly.' : 'Enter your bank details to display to customers.')
                    ->schema([
                        TextInput::make('gateway')
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        Toggle::make('is_enabled')
                            ->label('Enabled')
                            ->default(true),
                        Select::make('mode')
                            ->options([
                                'sandbox' => 'Sandbox',
                                'production' => 'Production',
                            ])
                            ->required()
                            ->default('sandbox'),
                        TextInput::make('public_key')
                            ->label('Midtrans client key')
                            ->password()
                            ->revealable()
                            ->helperText('Leave blank on edit to keep the existing encrypted client key.')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->maxLength(2000)
                            ->visible(fn (callable $get) => $get('gateway') === 'midtrans'),
                        TextInput::make('secret_key')
                            ->label('Midtrans server key')
                            ->password()
                            ->revealable()
                            ->helperText('Leave blank on edit to keep the existing encrypted server key.')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->maxLength(2000)
                            ->visible(fn (callable $get) => $get('gateway') === 'midtrans'),
                        Repeater::make('manual_bank_accounts')
                            ->label('Bank Accounts')
                            ->helperText('These accounts will be displayed to the customer when they checkout.')
                            ->schema([
                                TextInput::make('bank_name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('account_name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('account_number')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['bank_name'] ?? null)
                            ->visible(fn (callable $get) => $get('gateway') === 'manual')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Public payment copy')
                    ->description('Shown in booking, confirmation, payment page, WhatsApp handoff, and invoice email.')
                    ->schema([
                        TextInput::make('public_label')
                            ->label('Public payment label')
                            ->required()
                            ->default('Secure Midtrans payment link')
                            ->maxLength(255),
                        Textarea::make('booking_note')
                            ->label('Booking confirmation note')
                            ->required()
                            ->rows(3)
                            ->default('You won\'t be charged yet. We will send a secure payment link once your booking is confirmed.'),
                        Textarea::make('usd_note')
                            ->label('USD-IDR payment explanation')
                            ->required()
                            ->rows(3)
                            ->default('International cards accepted. Final billing is securely processed in IDR.'),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
                Section::make('Exchange rate')
                    ->description('Used when a USD quote is converted to the IDR amount charged by Midtrans.')
                    ->schema([
                        Select::make('exchange_rate_provider')
                            ->label('Provider')
                            ->options(['frankfurter' => 'Frankfurter'])
                            ->required()
                            ->default('frankfurter'),
                        TextInput::make('exchange_rate_buffer_percent')
                            ->label('USD-IDR buffer %')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(99)
                            ->required()
                            ->default(2),
                        TextInput::make('exchange_rate_cache_ttl_hours')
                            ->label('Cache TTL hours')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(168)
                            ->required()
                            ->default(12),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }
}