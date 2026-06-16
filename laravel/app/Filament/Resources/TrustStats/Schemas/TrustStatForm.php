<?php

namespace App\Filament\Resources\TrustStats\Schemas;

use App\Filament\Support\AdminForm;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TrustStatForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Display settings')
                    ->schema([
                        TextInput::make('icon_key')->maxLength(255),
                        TextInput::make('sort_order')->numeric()->default(0),
                        Toggle::make('is_active')->default(true),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                AdminForm::localized('title', 'Title', required: true),
                AdminForm::localized('value', 'Value', required: true),
            ]);
    }
}
