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
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->helperText("Unique identifier for the destination page URL (e.g., 'mount-bromo')."),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('The name of the destination (e.g., \'Mount Bromo\').'),
                        TextInput::make('region')
                            ->maxLength(255)
                            ->helperText('The region or area of this destination (e.g., \'Malang Regency\').'),
                        TextInput::make('province')
                            ->maxLength(255)
                            ->helperText('The province where the destination is located (e.g., \'East Java\').'),
                        AdminForm::imageUpload('cover_image', 'Cover image', 'admin/destinations/covers')
                            ->helperText('Upload a cover photo for this destination. Recommended: landscape aspect ratio.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                AdminForm::localized('short_description', 'Short description', textarea: true)
                    ->description('A brief summary of the destination, shown on listings and cards. Translate to ID, US, and CN.'),
                Section::make('Publishing')
                    ->schema([
                        TextInput::make('sort_order')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->helperText('Display priority. Lower numbers are displayed first on the website.'),
                        Toggle::make('is_featured')
                            ->required()
                            ->helperText('Promote this destination to the homepage\'s featured section.'),
                        Toggle::make('is_active')
                            ->required()
                            ->helperText('Toggle visibility on the public site.'),
                    ])
                    ->columns(3),
            ]);
    }
}
