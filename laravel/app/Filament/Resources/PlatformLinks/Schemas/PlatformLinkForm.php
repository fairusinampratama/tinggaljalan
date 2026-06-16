<?php

namespace App\Filament\Resources\PlatformLinks\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PlatformLinkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Platform link')
                    ->schema([
                        TextInput::make('name')->required()->maxLength(255),
                        TextInput::make('url')->url()->required()->maxLength(255),
                        TextInput::make('logo')->label('Logo path')->maxLength(255),
                        TextInput::make('alt')->label('Alt text')->maxLength(255),
                        TextInput::make('sort_order')->numeric()->default(0),
                        Toggle::make('is_active')->default(true),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
