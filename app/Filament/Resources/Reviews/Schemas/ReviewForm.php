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
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('The reviewer\'s display name (e.g., \'John Doe\' or \'Sarah M.\').'),
                        TextInput::make('rating')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(5)
                            ->default(5)
                            ->required()
                            ->helperText('Number of stars awarded, from 1 to 5.'),
                        TextInput::make('review_count')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Total reviews written by this user, or rating count if sourced from a platform.'),
                        TextInput::make('sort_order')->required()->numeric()->default(0)->helperText('Lower numbers appear first.'),
                        Toggle::make('is_featured')->required()
                            ->default(fn (): bool => Review::query()->active()->featured()->count() < Review::MAX_ACTIVE_FEATURED)
                            ->helperText('Only three active reviews can be featured at once.'),
                        Toggle::make('is_active')
                            ->required()
                            ->default(true)
                            ->helperText('Toggle to control review visibility on the public site.'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                AdminForm::localized('origin', 'Origin')
                    ->description('Where the reviewer is from (e.g., \'Singapore\' or \'Jakarta, Indonesia\'). Translate to ID, US, and CN.'),
                AdminForm::localized('source', 'Source')
                    ->description('The source platform of the review (e.g., \'Google Reviews\' or \'TripAdvisor\'). Translate to ID, US, and CN.'),
                AdminForm::localized('text', 'Review text', required: true, textarea: true)
                    ->description('The full testimonial or review comment. Translate to ID, US, and CN.'),
            ]);
    }
}