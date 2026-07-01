<?php

namespace App\Filament\Resources\Reviews\Schemas;

use App\Filament\Support\AdminForm;
use App\Models\Review;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Homepage review')
                    ->description('Up to three active featured reviews appear on the homepage.')
                    ->schema([
                        TextInput::make('name')->required()->maxLength(255),
                        TextInput::make('rating')->numeric()->minValue(0)->maxValue(5)->default(5)->required(),
                        TextInput::make('review_count')->required()->numeric()->minValue(0),
                        TextInput::make('sort_order')->required()->numeric()->default(0)->helperText('Lower numbers appear first.'),
                        Toggle::make('is_featured')->required()
                            ->default(fn (): bool => Review::query()->active()->featured()->count() < Review::MAX_ACTIVE_FEATURED)
                            ->helperText('Only three active reviews can be featured at once.'),
                        Toggle::make('is_active')->required()->default(true),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                AdminForm::localized('origin', 'Origin'),
                AdminForm::localized('source', 'Source'),
                AdminForm::localized('text', 'Review text', required: true, textarea: true),
            ]);
    }
}