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
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->helperText("Unique category slug used in news and guide URLs (e.g., 'travel-guides')."),
                        TextInput::make('sort_order')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->helperText('Category display order. Lower numbers appear first.'),
                        Toggle::make('is_active')
                            ->required()
                            ->default(true)
                            ->helperText('Toggle category visibility. Inactive categories hide their posts from listing pages.'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                AdminForm::localized('label', 'Label', required: true)
                    ->description('The public display name for this category. Translate to ID, US, and CN.'),
            ]);
    }
}
