<?php

namespace App\Filament\Resources\Vouchers\Schemas;

use App\Filament\Support\AdminForm;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VoucherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Voucher details')
                    ->schema([
                        TextInput::make('code')->required()->maxLength(255),
                        TextInput::make('label')->required()->maxLength(255),
                        Select::make('discount_type')
                            ->options([
                                'percent' => 'Percent',
                                'fixed' => 'Fixed amount',
                            ])
                            ->required(),
                        TextInput::make('discount_value')->numeric()->required(),
                        Select::make('currency')
                            ->options([
                                'IDR' => 'IDR',
                                'USD' => 'USD',
                            ]),
                        TextInput::make('usage_limit')->numeric()->minValue(0),
                        DateTimePicker::make('starts_at'),
                        DateTimePicker::make('ends_at'),
                        Toggle::make('is_active')->default(true),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                AdminForm::json('allowed_currencies', 'Allowed currencies', 3),
            ]);
    }
}
