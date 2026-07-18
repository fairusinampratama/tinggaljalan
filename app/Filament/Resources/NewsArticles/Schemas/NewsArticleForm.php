<?php

namespace App\Filament\Resources\NewsArticles\Schemas;

use App\Filament\Support\AdminForm;
use App\Filament\Support\NewsArticleReadiness;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

class NewsArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Basic info')
                        ->description('Category, destination, URL, and cover image.')
                        ->schema(self::basicInfoSchema())
                        ->columns(2),
                    Step::make('Article content')
                        ->description('Main text, excerpt, and sections.')
                        ->schema(self::articleContentSchema())
                        ->columns(1),
                    Step::make('Optional translations')
                        ->description('Leave empty to use English automatically. Adapt translations for each reader instead of copying literal machine-style text.')
                        ->schema(self::translationSchema())
                        ->columns(2),
                    Step::make('Publishing & Meta')
                        ->description('Publishing status, SEO, and tags.')
                        ->schema(self::publishingMetaSchema())
                        ->columns(2),
                ])
                    ->skippable()
                    ->persistStepInQueryString('news-article-step')
                    ->columnSpanFull(),
            ]);
    }

    private static function basicInfoSchema(): array
    {
        return [
            Select::make('article_category_id')
                ->relationship('articleCategory', 'slug')
                ->searchable()
                ->preload()
                ->helperText('The category this article belongs to, for example Travel Tips or News.')
                ->required(),
            Select::make('destination_id')
                ->relationship('destination', 'name')
                ->searchable()
                ->preload()
                ->helperText('Optional destination linked to this article. Helps with related route suggestions.'),
            TextInput::make('slug')
                ->helperText('URL text for this article, for example mount-bromo-guide. Keep it lowercase with hyphens.')
                ->required()
                ->dehydrateStateUsing(fn ($state): string => trim((string) $state))
                ->maxLength(255)
                ->columnSpanFull(),
            AdminForm::imageUpload('cover_image', 'Cover image', 'admin/news/covers')
                ->helperText('Main image used for the article header and social media previews.')
                ->columnSpanFull(),
            AdminForm::primaryLocalizedField('cover_alt', 'Cover alt text')
                ->helperText('Short image description for accessibility and SEO.')
                ->columnSpanFull(),
            AdminForm::primaryLocalizedField('reading_time', 'Reading time')
                ->helperText('Estimated reading time shown to readers, for example 5 min read.')
                ->columnSpanFull(),
        ];
    }

    private static function articleContentSchema(): array
    {
        return [
            AdminForm::primaryLocalizedField('title', 'Title', required: true)
                ->helperText('Main article title shown on the blog and search engines.'),
            AdminForm::primaryLocalizedField('excerpt', 'Excerpt', required: true, textarea: true)
                ->helperText('Short summary used on blog listing cards and search previews.'),
            Repeater::make('sections')
                ->schema([
                    AdminForm::primaryLocalizedField('heading', 'Heading', required: true)
                        ->helperText('Subtitle for this section of the article.'),
                    AdminForm::primaryLocalizedField('body', 'Body', required: true, textarea: true)
                        ->helperText('Main content for this section. Add one main topic per section.'),
                    Section::make('Translations')
                        ->description('Optional Indonesian and Chinese adaptations. Keep the meaning, but write naturally for each reader.')
                        ->schema([
                            ...AdminForm::translationFields('heading', 'Heading'),
                            ...AdminForm::translationFields('body', 'Body', textarea: true),
                        ])
                        ->columns(2)
                        ->collapsed()
                        ->collapsible()
                        ->columnSpanFull(),
                ])
                ->defaultItems(0)
                ->reorderable()
                ->columnSpanFull(),
        ];
    }

    private static function translationSchema(): array
    {
        return [
            Section::make('Optional page translations')
                ->description('Leave these empty unless you need custom Indonesian or Chinese text. Empty translations use English automatically.')
                ->schema([
                    ...AdminForm::translationFields('title', 'Title'),
                    ...AdminForm::translationFields('excerpt', 'Excerpt', textarea: true),
                    ...AdminForm::translationFields('cover_alt', 'Cover alt text'),
                    ...AdminForm::translationFields('reading_time', 'Reading time'),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    private static function publishingMetaSchema(): array
    {
        return [
            Section::make('Publishing')
                ->schema([
                    Select::make('status')
                        ->options(['draft' => 'Draft', 'published' => 'Published'])
                        ->helperText('Drafts are hidden. Publishing requires all content essentials to be complete.')
                        ->rules([
                            fn (Get $get): \Closure => function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                if ($value !== 'published') {
                                    return;
                                }

                                $missing = NewsArticleReadiness::missingItemsFromState([
                                    'article_category_id' => $get('article_category_id'),
                                    'title' => ['us' => $get('title.us')],
                                    'slug' => $get('slug'),
                                    'cover_image' => $get('cover_image'),
                                    'cover_alt' => ['us' => $get('cover_alt.us')],
                                    'excerpt' => ['us' => $get('excerpt.us')],
                                    'sections' => $get('sections'),
                                ]);

                                if ($missing !== []) {
                                    $fail('Cannot publish. Complete the following: '.implode(', ', $missing).'.');
                                }
                            },
                        ])
                        ->required()
                        ->default('draft'),
                    Toggle::make('is_featured')->required()
                        ->label('Feature this article')
                        ->helperText('Highlights this article on the main blog page or homepage.')
                        ->required(),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Related routes')
                ->description('Link this article to specific tour packages.')
                ->schema([
                    Select::make('tourPackages')
                        ->relationship(
                            name: 'tourPackages',
                            titleAttribute: 'slug',
                            modifyQueryUsing: fn ($query) => $query->orderBy('slug'),
                        )
                        ->getOptionLabelFromRecordUsing(fn ($record): string => $record->title['us'] ?? $record->slug)
                        ->helperText('Select related tour packages to show at the bottom of the article.')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
            Section::make('Reader-facing tags')
                ->description('Tags are visible on article cards. English is edited first; Indonesian and Chinese can be adapted inside each tag and fall back to English when empty.')
                ->schema([
                    AdminForm::primaryLocalizedRepeater('tags', 'Tags')
                        ->helperText('Add short topics such as Bromo, Family Trip, or Booking Tips. Use translations that feel natural to readers, not literal machine copies.'),
                ])
                ->columnSpanFull(),
        ];
    }
}
