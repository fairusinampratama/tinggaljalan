<?php

namespace App\Filament\Resources\HeroSlides;

use App\Filament\Support\AdminForm;
use App\Models\HeroSlide;
use App\Support\PublicSite;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Slider;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class HeroSlideResource extends Resource
{
    protected static ?string $model = HeroSlide::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        Group::make([
                            Section::make('Promotion essentials')
                                ->description('Add the artwork and only the customer-facing content this promotion needs. Leave copy and buttons empty for an image-only slide.')
                                ->schema([
                                    TextInput::make('admin_label')
                                        ->label('Internal name')
                                        ->placeholder('July Bromo Promotion')
                                        ->required()
                                        ->maxLength(100)
                                        ->helperText('Only shown in the admin. Customers will not see this name.'),
                                    Grid::make(2)->schema([
                                        AdminForm::imageUpload('desktop_image', 'Desktop artwork', 'admin/hero', multiple: false)
                                            ->required()
                                            ->live()
                                            ->helperText('Recommended: 2880 x 1160 px. Keep important content away from the edges.'),
                                        AdminForm::imageUpload('mobile_image', 'Mobile artwork (optional)', 'admin/hero', multiple: false)
                                            ->live()
                                            ->helperText('Recommended: 800 x 1080 px. Desktop artwork is used when empty.'),
                                    ]),
                                    AdminForm::primaryLocalizedField('image_alt', 'Accessible image description')
                                        ->required()
                                        ->maxLength(180)
                                        ->live(onBlur: true)
                                        ->helperText('Describe the promotion or meaningful image content for screen-reader users.'),
                                    AdminForm::primaryLocalizedField('eyebrow', 'Small label')
                                        ->maxLength(50)
                                        ->live(onBlur: true)
                                        ->placeholder('Limited offer'),
                                    AdminForm::primaryLocalizedField('heading', 'Heading')
                                        ->maxLength(90)
                                        ->live(onBlur: true)
                                        ->placeholder('Explore Bromo at sunrise'),
                                    AdminForm::primaryLocalizedField('description', 'Description', textarea: true)
                                        ->maxLength(240)
                                        ->live(onBlur: true)
                                        ->rows(3),
                                    Grid::make(2)->schema([
                                        ToggleButtons::make('primary_cta_preset')
                                            ->label('Primary button destination')
                                            ->options([
                                                'none' => 'No button',
                                                'routes' => 'Explore routes',
                                                'booking' => 'Booking',
                                                'whatsapp' => 'WhatsApp',
                                                'custom' => 'Custom',
                                            ])
                                            ->default('none')
                                            ->inline()
                                            ->live()
                                            ->dehydrated(false)
                                            ->afterStateHydrated(function (ToggleButtons $component, Get $get): void {
                                                $url = $get('data.primary_cta_url', isAbsolute: true);
                                                $component->state(match ($url) {
                                                    null, '' => 'none',
                                                    '/routes' => 'routes',
                                                    '/booking' => 'booking',
                                                    PublicSite::whatsappBase() => 'whatsapp',
                                                    default => 'custom',
                                                });
                                            })
                                            ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                                                $destinations = [
                                                    'routes' => [
                                                        '/routes',
                                                        ['us' => 'Explore routes', 'id' => 'Jelajahi paket', 'cn' => '探索套餐'],
                                                    ],
                                                    'booking' => [
                                                        '/booking',
                                                        ['us' => 'Book a trip', 'id' => 'Pesan perjalanan', 'cn' => '预订行程'],
                                                    ],
                                                    'whatsapp' => [
                                                        PublicSite::whatsappBase(),
                                                        ['us' => 'Chat on WhatsApp', 'id' => 'Chat di WhatsApp', 'cn' => '在WhatsApp上聊天'],
                                                    ],
                                                ];

                                                if ($state === 'none') {
                                                    $set('data.primary_cta_url', null, isAbsolute: true);
                                                    foreach (['us', 'id', 'cn'] as $language) {
                                                        $set("data.primary_cta_label.{$language}", null, isAbsolute: true);
                                                    }
                                                    return;
                                                }

                                                if (isset($destinations[$state])) {
                                                    [$url, $labels] = $destinations[$state];
                                                    $set('data.primary_cta_url', $url, isAbsolute: true);
                                                    
                                                    foreach (['us', 'id', 'cn'] as $language) {
                                                        if (blank($get("data.primary_cta_label.{$language}", isAbsolute: true))) {
                                                            $set("data.primary_cta_label.{$language}", $labels[$language] ?? null, isAbsolute: true);
                                                        }
                                                    }
                                                }

                                                if ($state === 'custom') {
                                                    $set('data.primary_cta_url', null, isAbsolute: true);
                                                }
                                            }),
                                        AdminForm::primaryLocalizedField('primary_cta_label', 'Primary button label')
                                            ->maxLength(40)
                                            ->live(onBlur: true)
                                            ->visible(fn (Get $get): bool => $get('data.primary_cta_preset', isAbsolute: true) !== 'none')
                                            ->required(fn (Get $get): bool => filled($get('data.primary_cta_url', isAbsolute: true))),
                                    ]),
                                    TextInput::make('primary_cta_url')
                                        ->label('Destination URL')
                                        ->maxLength(2048)
                                        ->live(onBlur: true)
                                        ->visible(fn (Get $get): bool => $get('data.primary_cta_preset', isAbsolute: true) !== 'none')
                                        ->readOnly(fn (Get $get): bool => $get('data.primary_cta_preset', isAbsolute: true) !== 'custom')
                                        ->required(fn (Get $get): bool => filled($get('data.primary_cta_label.us', isAbsolute: true)))
                                        ->regex('/^(?:\/(?!\/)[^\s]*|https:\/\/[^\s]+|mailto:[^\s]+|tel:\+?[0-9][0-9\s().-]*)$/i')
                                        ->helperText('Choose a preset or enter an internal /path, https://, mailto:, or tel: destination.'),
                                    Toggle::make('is_active')
                                        ->label('Active')
                                        ->default(true)
                                        ->helperText('Active slides still follow their optional schedule.'),
                                ])
                                ->columns(1),

                            Section::make('Translations')
                                ->description('Indonesian and Chinese fields are optional. Empty values automatically use English.')
                                ->schema([
                                    Tabs::make('Hero slide translations')
                                        ->tabs([
                                            Tab::make('Indonesian')
                                                ->schema(self::translationTab('id', 'Indonesian')),
                                            Tab::make('Chinese')
                                                ->schema(self::translationTab('cn', 'Chinese')),
                                        ]),
                                ])
                                ->collapsed()
                                ->collapsible(),

                            Section::make('Design & advanced')
                                ->description('The defaults work for most artwork. Adjust these only when the image or text needs it.')
                                ->schema([
                                    ToggleButtons::make('text_alignment')
                                        ->label('Content position')
                                        ->options([
                                            'left' => 'Left',
                                            'center' => 'Center',
                                            'right' => 'Right',
                                        ])
                                        ->default('left')
                                        ->inline()
                                        ->grouped()
                                        ->required()
                                        ->live(),
                                    Slider::make('overlay_strength')
                                        ->label('Text readability overlay')
                                        ->range(0, 80)
                                        ->step(20)
                                        ->default(40)
                                        ->tooltips()
                                        ->pips()
                                        ->pipsValues([0, 20, 40, 60, 80])
                                        ->live()
                                        ->helperText('0 None | 20 Light | 40 Medium | 60 Strong | 80 Maximum'),
                                    ToggleButtons::make('focal_position')
                                        ->label('Image focal point')
                                        ->options([
                                            'left top' => 'Top left',
                                            'top' => 'Top',
                                            'right top' => 'Top right',
                                            'left' => 'Left',
                                            'center' => 'Center',
                                            'right' => 'Right',
                                            'left bottom' => 'Bottom left',
                                            'bottom' => 'Bottom',
                                            'right bottom' => 'Bottom right',
                                        ])
                                        ->default('center')
                                        ->columns(3)
                                        ->required()
                                        ->live()
                                        ->helperText('Choose the part of the image that must remain visible when cropped.'),
                                    Toggle::make('has_secondary_cta')
                                        ->label('Add secondary button')
                                        ->live()
                                        ->dehydrated(false)
                                        ->afterStateHydrated(fn (Toggle $component, Get $get) => $component->state(
                                            filled($get('data.secondary_cta_label.us', isAbsolute: true))
                                            || filled($get('data.secondary_cta_url', isAbsolute: true))
                                        ))
                                        ->afterStateUpdated(function (bool $state, Set $set): void {
                                            if (! $state) {
                                                foreach (['us', 'id', 'cn'] as $language) {
                                                    $set("data.secondary_cta_label.{$language}", null, isAbsolute: true);
                                                }
                                                $set('data.secondary_cta_url', null, isAbsolute: true);
                                            }
                                        }),
                                    Grid::make(2)
                                        ->schema([
                                            AdminForm::primaryLocalizedField('secondary_cta_label', 'Secondary button label')
                                                ->maxLength(40)
                                                ->live(onBlur: true)
                                                ->required(fn (Get $get): bool => filled($get('data.secondary_cta_url', isAbsolute: true))),
                                            TextInput::make('secondary_cta_url')
                                                ->label('Secondary destination URL')
                                                ->maxLength(2048)
                                                ->live(onBlur: true)
                                                ->required(fn (Get $get): bool => filled($get('data.secondary_cta_label.us', isAbsolute: true)))
                                                ->regex('/^(?:\/(?!\/)[^\s]*|https:\/\/[^\s]+|mailto:[^\s]+|tel:\+?[0-9][0-9\s().-]*)$/i'),
                                        ])
                                        ->visible(fn (Get $get): bool => (bool) $get('data.has_secondary_cta', isAbsolute: true)),
                                ])
                                ->collapsed()
                                ->collapsible(),

                            Section::make('Schedule')
                                ->description('Leave scheduling off to show an active slide immediately and indefinitely.')
                                ->schema([
                                    Toggle::make('has_schedule')
                                        ->label('Use a publishing schedule')
                                        ->live()
                                        ->dehydrated(false)
                                        ->afterStateHydrated(fn (Toggle $component, Get $get) => $component->state(
                                            filled($get('data.start_date', isAbsolute: true))
                                            || filled($get('data.end_date', isAbsolute: true))
                                        ))
                                        ->afterStateUpdated(function (bool $state, Set $set): void {
                                            if (! $state) {
                                                $set('data.start_date', null, isAbsolute: true);
                                                $set('data.end_date', null, isAbsolute: true);
                                            }
                                        }),
                                    Grid::make(2)
                                        ->schema([
                                            DateTimePicker::make('start_date')
                                                ->label('Publish from'),
                                            DateTimePicker::make('end_date')
                                                ->label('Publish until')
                                                ->afterOrEqual('start_date'),
                                        ])
                                        ->visible(fn (Get $get): bool => (bool) $get('data.has_schedule', isAbsolute: true)),
                                ])
                                ->collapsed()
                                ->collapsible(),

                            Hidden::make('sort_order'),
                        ])
                            ->columnSpan(['default' => 3, 'lg' => 2]),

                        Group::make([
                            ToggleButtons::make('preview_mode')
                                ->label('Preview size')
                                ->options([
                                    'desktop' => 'Desktop',
                                    'mobile' => 'Mobile',
                                ])
                                ->default('desktop')
                                ->inline()
                                ->grouped()
                                ->live()
                                ->dehydrated(false),
                            View::make('filament.forms.components.hero-slide-preview'),
                        ])
                            ->extraAttributes(['class' => 'lg:sticky lg:top-6'])
                            ->columnSpan(['default' => 3, 'lg' => 1]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private static function translationTab(string $language, string $label): array
    {
        return [
            TextInput::make("image_alt.{$language}")
                ->label("Accessible image description - {$label}")
                ->maxLength(180),
            TextInput::make("eyebrow.{$language}")
                ->label("Small label - {$label}")
                ->maxLength(50),
            TextInput::make("heading.{$language}")
                ->label("Heading - {$label}")
                ->maxLength(90),
            Textarea::make("description.{$language}")
                ->label("Description - {$label}")
                ->maxLength(240)
                ->rows(3),
            TextInput::make("primary_cta_label.{$language}")
                ->label("Primary button label - {$label}")
                ->maxLength(40),
            TextInput::make("secondary_cta_label.{$language}")
                ->label("Secondary button label - {$label}")
                ->maxLength(40),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('desktop_image')
                    ->label('Artwork')
                    ->square()
                    ->disk('public'),
                TextColumn::make('admin_label')
                    ->label('Promotion')
                    ->state(fn (HeroSlide $record): string => $record->displayLabel())
                    ->description(fn (HeroSlide $record): ?string => data_get($record->heading, 'us'))
                    ->searchable(),
                TextColumn::make('publication_status')
                    ->label('Status')
                    ->state(fn (HeroSlide $record): string => $record->publicationStatus())
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Active' => 'success',
                        'Scheduled' => 'info',
                        'Upcoming' => 'warning',
                        'Expired' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('schedule')
                    ->label('Schedule')
                    ->state(function (HeroSlide $record): string {
                        if (! $record->start_date && ! $record->end_date) {
                            return 'Always';
                        }

                        return ($record->start_date?->format('d M Y H:i') ?? 'Now')
                            .' to '
                            .($record->end_date?->format('d M Y H:i') ?? 'No end');
                    })
                    ->wrap(),
                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Active'),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order', 'asc')
            ->description(fn (): string => HeroSlide::query()->activeScheduled()->count() > 5
                ? 'Warning: more than five slides are currently eligible. Only the first five in this order appear publicly.'
                : 'Drag rows to change order. Up to five active slides eligible for the current schedule appear publicly.')
            ->recordActions([
                ReplicateAction::make()
                    ->label('Duplicate')
                    ->mutateRecordDataUsing(function (array $data, HeroSlide $record): array {
                        $data['admin_label'] = ($record->displayLabel()).' - Copy';
                        $data['is_active'] = false;
                        $data['start_date'] = null;
                        $data['end_date'] = null;
                        $data['sort_order'] = ((int) HeroSlide::query()->max('sort_order')) + 10;

                        return $data;
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHeroSlides::route('/'),
            'create' => Pages\CreateHeroSlide::route('/create'),
            'edit' => Pages\EditHeroSlide::route('/{record}/edit'),
        ];
    }
}
