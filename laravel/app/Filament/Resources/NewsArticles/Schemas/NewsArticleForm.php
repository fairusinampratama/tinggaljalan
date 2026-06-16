<?php

namespace App\Filament\Resources\NewsArticles\Schemas;

use App\Filament\Support\AdminForm;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NewsArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Article identity')
                    ->schema([
                        Select::make('destination_id')->relationship('destination', 'name')->searchable()->preload(),
                        Select::make('article_category_id')->relationship('articleCategory', 'slug')->searchable()->preload()->required(),
                        TextInput::make('slug')->required()->maxLength(255),
                        TextInput::make('cover_image')->label('Cover image path')->maxLength(255),
                    ])
                    ->columns(2),
                AdminForm::localized('title', 'Title', required: true),
                AdminForm::localized('excerpt', 'Excerpt', required: true, textarea: true),
                AdminForm::localized('cover_alt', 'Cover alt text'),
                AdminForm::localized('reading_time', 'Reading time'),
                Repeater::make('sections')
                    ->schema([
                        AdminForm::localized('heading', 'Heading', required: true),
                        AdminForm::localized('body', 'Body', required: true, textarea: true),
                    ])
                    ->defaultItems(0)
                    ->reorderable()
                    ->columnSpanFull(),
                Section::make('Publishing')
                    ->schema([
                        Select::make('status')
                            ->options(['draft' => 'Draft', 'published' => 'Published'])
                            ->required()
                            ->default('draft'),
                        DateTimePicker::make('published_at'),
                        DateTimePicker::make('content_updated_at'),
                        Toggle::make('is_featured')->required(),
                    ])
                    ->columns(4),
                CheckboxList::make('tourPackages')
                    ->relationship(
                        name: 'tourPackages',
                        titleAttribute: 'slug',
                        modifyQueryUsing: fn ($query) => $query->orderBy('slug'),
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->title['us'] ?? $record->slug)
                    ->columns(2)
                    ->columnSpanFull(),
                AdminForm::json('tags', 'Tags'),
                AdminForm::json('seo', 'SEO metadata'),
            ]);
    }
}
