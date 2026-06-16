<?php

namespace App\Filament\Resources\Destinations\Schemas;

use App\Filament\Support\AdminForm;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DestinationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Destination')
                    ->schema([
                        TextInput::make('slug')->required()->maxLength(255),
                        TextInput::make('name')->required()->maxLength(255),
                        TextInput::make('region')->maxLength(255),
                        TextInput::make('province')->maxLength(255),
                        TextInput::make('cover_image')->label('Cover image path')->maxLength(255)->columnSpanFull(),
                    ])
                    ->columns(2),
                AdminForm::localized('short_description', 'Short description', textarea: true),
                AdminForm::localized('description', 'Long description', textarea: true),
                Section::make('Publishing')
                    ->schema([
                        TextInput::make('sort_order')->required()->numeric()->default(0),
                        Toggle::make('is_featured')->required(),
                        Toggle::make('is_active')->required(),
                    ])
                    ->columns(3),
                AdminForm::json('gallery', 'Gallery image paths'),
                AdminForm::json('source_refs', 'Source references'),
                AdminForm::json('seo', 'SEO metadata'),
            ]);
    }
}
