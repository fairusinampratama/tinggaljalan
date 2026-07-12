<?php

namespace App\Filament\Resources\Faqs\Schemas;

use App\Filament\Support\AdminForm;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('FAQ display')
                    ->description('Active FAQs appear on the homepage and every package detail page.')
                    ->schema([
                        TextInput::make('sort_order')->required()
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first.'),
                        Toggle::make('is_active')
                            ->required()
                            ->default(true)
                            ->helperText('Toggle to display or hide this FAQ on public pages.'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                AdminForm::localized('question', 'Question', required: true)
                    ->description('The question text. Translate to ID, US, and CN.'),
                AdminForm::localized('answer', 'Answer', required: true, textarea: true)
                    ->description('The answer text. Supports multi-line explanations. Translate to ID, US, and CN.'),
            ]);
    }
}