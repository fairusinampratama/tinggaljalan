<?php

namespace App\Filament\Resources\AddOns\Schemas;

use App\Filament\Support\AdminForm;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AddOnForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Add-on details')
                    ->schema([
                        TextInput::make('slug')->required()->maxLength(255),
                        Select::make('pricing_type')
                            ->options([
                                'per_booking' => 'Per booking',
                                'per_pax' => 'Per guest',
                            ])
                            ->required()
                            ->default('per_booking'),
                        TextInput::make('price_idr')->label('Price IDR')->numeric()->prefix('Rp'),
                        TextInput::make('price_usd')->label('Price USD')->numeric()->prefix('$'),
                        Toggle::make('is_active')->default(true),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                AdminForm::localized('title', 'Title', required: true),
                AdminForm::localized('description', 'Description', textarea: true),
            ]);
    }
}
