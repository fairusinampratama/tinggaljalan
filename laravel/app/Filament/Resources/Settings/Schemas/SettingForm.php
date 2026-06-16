<?php

namespace App\Filament\Resources\Settings\Schemas;

use App\Filament\Support\AdminForm;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Setting key')
                    ->schema([
                        TextInput::make('group')->required()->maxLength(255),
                        TextInput::make('key')->required()->maxLength(255),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                AdminForm::json('value', 'Value', 10),
            ]);
    }
}
