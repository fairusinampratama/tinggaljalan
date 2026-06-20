<?php

namespace App\Filament\Resources\RouteFilters\Schemas;

use App\Filament\Support\AdminForm;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class RouteFilterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Filter settings')
                    ->description('These filters appear as chips on the public routes page and as choices inside Tour Packages.')
                    ->schema([
                        AdminForm::primaryLocalizedField('label', 'English label', required: true)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (?string $state, callable $set, callable $get) => blank($get('slug')) ? $set('slug', Str::slug($state ?? '')) : null)
                            ->helperText('Customer-facing filter name, for example Honeymoon or Family.'),
                        TextInput::make('slug')
                            ->helperText('Stable URL/filter value. Use lowercase words with hyphens, for example honeymoon.')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first. Keep Recommended at 0.'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive filters stay in admin but disappear from public route chips.'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Optional translations')
                    ->description('Leave empty to use the English label.')
                    ->schema([
                        ...AdminForm::translationFields('label', 'Label'),
                        ...AdminForm::translationFields('description', 'Description', textarea: true),
                    ])
                    ->columns(2)
                    ->collapsed()
                    ->collapsible()
                    ->columnSpanFull(),
                AdminForm::primaryLocalizedField('description', 'English description', textarea: true)
                    ->helperText('Optional internal note for what this filter should mean.')
                    ->columnSpanFull(),
            ]);
    }
}
