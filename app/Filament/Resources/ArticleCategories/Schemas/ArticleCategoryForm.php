<?php

namespace App\Filament\Resources\ArticleCategories\Schemas;

use App\Filament\Support\AdminForm;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ArticleCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Category settings')
                    ->schema([
                        TextInput::make('slug')->required()->maxLength(255),
                        TextInput::make('sort_order')->required()->numeric()->default(0),
                        Toggle::make('is_active')->required()->default(true),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                AdminForm::localized('label', 'Label', required: true),
            ]);
    }
}
