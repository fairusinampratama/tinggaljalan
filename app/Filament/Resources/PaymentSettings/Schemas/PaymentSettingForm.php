<?php

namespace App\Filament\Resources\PaymentSettings\Schemas;

use App\Models\PaymentSetting;
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
                Section::make('Midtrans gateway')
                    ->description('Midtrans is the only payment gateway for v1. Keys are stored encrypted and never exposed publicly.')
                    ->schema([
                        TextInput::make('gateway')
                            ->default(PaymentSetting::GATEWAY_MIDTRANS)
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
                            ->maxLength(2000),
                        TextInput::make('secret_key')
                            ->label('Midtrans server key')
                            ->password()
                            ->revealable()
                            ->helperText('Leave blank on edit to keep the existing encrypted server key.')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->maxLength(2000),
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
                            ->default('Payment is requested only after our team confirms availability.'),
                        Textarea::make('usd_note')
                            ->label('USD-IDR payment explanation')
                            ->required()
                            ->rows(3)
                            ->default('USD quotes are converted to IDR when payment is requested. Midtrans processes payments in IDR.'),
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