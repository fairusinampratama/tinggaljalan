<?php

namespace App\Filament\Support;

use App\Support\PublicSite;
use App\Support\ResponsiveImageGenerator;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AdminForm
{
    public static function localized(string $field, string $label, bool $required = false, bool $textarea = false): Section
    {
        return Section::make($label)
            ->schema(self::localizedFields($field, $required, $textarea))
            ->columns(3)
            ->columnSpanFull();
    }

    public static function localizedFields(string $field, bool $required = false, bool $textarea = false): array
    {
        $component = $textarea ? Textarea::class : TextInput::class;

        return [
            $component::make("{$field}.id")
                ->label('Indonesian')
                ->required($required),
            $component::make("{$field}.us")
                ->label('English')
                ->required($required),
            $component::make("{$field}.cn")
                ->label('Chinese'),
        ];
    }

    public static function primaryLocalizedField(string $field, ?string $label = null, bool $required = false, bool $textarea = false): TextInput|Textarea
    {
        $component = $textarea ? Textarea::class : TextInput::class;

        return $component::make("{$field}.us")
            ->label($label ?? Str::headline($field))
            ->required($required);
    }

    /**
     * @return array<int, TextInput|Textarea>
     */
    public static function translationFields(string $field, ?string $label = null, bool $textarea = false): array
    {
        $component = $textarea ? Textarea::class : TextInput::class;
        $label ??= Str::headline($field);

        return [
            $component::make("{$field}.id")
                ->label("{$label} - Indonesian"),
            $component::make("{$field}.cn")
                ->label("{$label} - Chinese"),
        ];
    }

    public static function localizedRepeater(string $field, string $label, bool $textarea = false): Repeater
    {
        $component = $textarea ? Textarea::class : TextInput::class;

        return Repeater::make($field)
            ->label($label)
            ->formatStateUsing(fn ($state): array => self::normalizeLocalizedRepeaterState($state))
            ->dehydrateStateUsing(fn ($state): array => self::normalizeLocalizedRepeaterState($state))
            ->schema([
                $component::make('id')->label('Indonesian')->required(),
                $component::make('us')->label('English'),
                $component::make('cn')->label('Chinese'),
            ])
            ->columns(3)
            ->defaultItems(0)
            ->reorderable()
            ->columnSpanFull();
    }

    public static function primaryLocalizedRepeater(string $field, string $label, bool $required = false, bool $textarea = false): Repeater
    {
        $component = $textarea ? Textarea::class : TextInput::class;

        return Repeater::make($field)
            ->label($label)
            ->formatStateUsing(fn ($state): array => self::normalizeLocalizedRepeaterState($state))
            ->dehydrateStateUsing(fn ($state): array => self::normalizeLocalizedRepeaterState($state))
            ->schema([
                $component::make('us')
                    ->label('English')
                    ->required($required),
                Section::make('Translations')
                    ->schema([
                        $component::make('id')->label('Indonesian'),
                        $component::make('cn')->label('Chinese'),
                    ])
                    ->columns(2)
                    ->collapsed()
                    ->collapsible()
                    ->columnSpanFull(),
            ])
            ->columns(1)
            ->defaultItems(0)
            ->reorderable()
            ->columnSpanFull();
    }

    public static function pickupAreasRepeater(string $field = 'pickup_areas'): Repeater
    {
        return Repeater::make($field)
            ->label('Pickup areas')
            ->formatStateUsing(fn ($state): array => self::normalizePickupAreasState($state))
            ->dehydrateStateUsing(fn ($state): array => self::normalizePickupAreasState($state))
            ->schema([
                TextInput::make('us')
                    ->label('Pickup area')
                    ->placeholder('Malang hotel')
                    ->required(),
                Section::make('Translations')
                    ->schema([
                        TextInput::make('id')->label('Indonesian'),
                        TextInput::make('cn')->label('Chinese'),
                    ])
                    ->columns(2)
                    ->collapsed()
                    ->collapsible()
                    ->columnSpanFull(),
            ])
            ->helperText('Add one supported pickup place per row. These appear in the pickup details list on the route page.')
            ->columns(1)
            ->defaultItems(0)
            ->reorderable()
            ->reorderableWithButtons()
            ->columnSpanFull();
    }

    public static function testimonialsRepeater(string $field = 'testimonials'): Repeater
    {
        return Repeater::make($field)
            ->label('Testimonials')
            ->formatStateUsing(fn ($state): array => self::normalizeTestimonialsState($state))
            ->dehydrateStateUsing(fn ($state): array => self::normalizeTestimonialsState($state))
            ->schema([
                TextInput::make('name')
                    ->label('Traveler name')
                    ->placeholder('Sarah')
                    ->required(),
                TextInput::make('meta.us')
                    ->label('Meta/source')
                    ->placeholder('Google review')
                    ->columnSpanFull(),
                Textarea::make('quote.us')
                    ->label('Quote')
                    ->placeholder('The team made the trip easy to understand and well organized.')
                    ->rows(3)
                    ->required()
                    ->columnSpanFull(),
                Section::make('Translations')
                    ->schema([
                        TextInput::make('meta.id')->label('Meta/source - Indonesian'),
                        TextInput::make('meta.cn')->label('Meta/source - Chinese'),
                        Textarea::make('quote.id')->label('Quote - Indonesian')->rows(3),
                        Textarea::make('quote.cn')->label('Quote - Chinese')->rows(3),
                    ])
                    ->columns(2)
                    ->collapsed()
                    ->collapsible()
                    ->columnSpanFull(),
            ])
            ->helperText('Optional traveler proof shown on the route detail page.')
            ->columns(1)
            ->defaultItems(0)
            ->reorderable()
            ->reorderableWithButtons()
            ->columnSpanFull();
    }

    public static function json(string $field, string $label, int $rows = 5): Textarea
    {
        return Textarea::make($field)
            ->label($label)
            ->formatStateUsing(fn ($state): string => is_string($state) ? $state : json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
            ->dehydrateStateUsing(fn ($state): array|string|null => self::decodeJsonState($state))
            ->rows($rows)
            ->columnSpanFull();
    }

    public static function jsonRequired(string $field, string $label, int $rows = 5): Textarea
    {
        return self::json($field, $label, $rows)->required();
    }

    public static function imageUpload(string $field, string $label, string $directory, bool $multiple = false): FileUpload
    {
        return FileUpload::make($field)
            ->label($label)
            ->image()
            ->disk('public')
            ->directory($directory)
            ->visibility('public')
            ->fetchFileInformation(false)
            ->openable()
            ->downloadable()
            ->maxSize(4096)
            ->multiple($multiple)
            ->reorderable($multiple)
            ->appendFiles($multiple)
            ->saveUploadedFileUsing(static function (TemporaryUploadedFile $file) use ($directory): ?string {
                $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg';

                $path = $file->storeAs($directory, Str::ulid().'.'.$extension, 'public') ?: null;

                if ($path) {
                    app(ResponsiveImageGenerator::class)->generateForPublicDiskPath($path);
                }

                return $path;
            })
            ->getUploadedFileUsing(static function (string $file): ?array {
                if (blank($file)) {
                    return null;
                }

                return [
                    'name' => basename($file),
                    'size' => 0,
                    'type' => null,
                    'url' => PublicSite::assetPath($file),
                ];
            })
            ->getOpenableFileUrlUsing(static fn (string $file): ?string => blank($file) ? null : PublicSite::assetPath($file));
    }

    public static function galleryUpload(string $field, string $label, string $directory): FileUpload
    {
        return self::imageUpload($field, $label, $directory, multiple: true)
            ->panelLayout('grid')
            ->imagePreviewHeight('12rem')
            ->itemPanelAspectRatio('4:3')
            ->imageEditor()
            ->imageEditorViewportWidth(1280)
            ->imageEditorViewportHeight(960)
            ->imageEditorAspectRatioOptions([
                '16:9' => 'Wide',
                '4:3' => 'Standard',
                '1:1' => 'Square',
                null => 'Free',
            ]);
    }

    protected static function decodeJsonState(mixed $state): array|string|null
    {
        if ($state === null || $state === '') {
            return null;
        }

        if (is_array($state)) {
            return $state;
        }

        $decoded = json_decode($state, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $state;
    }

    protected static function normalizeLocalizedRepeaterState(mixed $state): array
    {
        if (! is_array($state)) {
            return [];
        }

        if ((isset($state['id']) || isset($state['us']) || isset($state['cn'])) && (is_array($state['id'] ?? null) || is_array($state['us'] ?? null) || is_array($state['cn'] ?? null))) {
            $count = max(count($state['id'] ?? []), count($state['us'] ?? []), count($state['cn'] ?? []));

            return collect(range(0, max(0, $count - 1)))
                ->map(fn (int $index): array => [
                    'id' => self::firstFilled($state['id'][$index] ?? null, $state['us'][$index] ?? null, $state['cn'][$index] ?? null),
                    'us' => self::firstFilled($state['us'][$index] ?? null, $state['id'][$index] ?? null, $state['cn'][$index] ?? null),
                    'cn' => self::firstFilled($state['cn'][$index] ?? null, $state['us'][$index] ?? null, $state['id'][$index] ?? null),
                ])
                ->filter(fn (array $item): bool => filled($item['id']) || filled($item['us']) || filled($item['cn']))
                ->values()
                ->all();
        }

        return collect($state)
            ->map(fn ($item): array => is_array($item) ? [
                'id' => self::firstFilled($item['id'] ?? null, $item['us'] ?? null, $item['cn'] ?? null),
                'us' => self::firstFilled($item['us'] ?? null, $item['id'] ?? null, $item['cn'] ?? null),
                'cn' => self::firstFilled($item['cn'] ?? null, $item['us'] ?? null, $item['id'] ?? null),
            ] : [
                'id' => (string) $item,
                'us' => (string) $item,
                'cn' => '',
            ])
            ->filter(fn (array $item): bool => filled($item['id']) || filled($item['us']) || filled($item['cn']))
            ->values()
            ->all();
    }

    protected static function normalizePickupAreasState(mixed $state): array
    {
        return self::normalizeLocalizedRepeaterState($state);
    }

    protected static function normalizeTestimonialsState(mixed $state): array
    {
        if (! is_array($state)) {
            return [];
        }

        return collect($state)
            ->map(function ($item): array {
                $item = is_array($item) ? $item : [];

                return [
                    'name' => self::firstFilled($item['name'] ?? null, 'Traveler'),
                    'meta' => self::normalizeLocalizedValue($item['meta'] ?? []),
                    'quote' => self::normalizeLocalizedValue($item['quote'] ?? $item['text'] ?? []),
                ];
            })
            ->filter(fn (array $item): bool => filled($item['quote']['us'] ?? null) || filled($item['quote']['id'] ?? null) || filled($item['quote']['cn'] ?? null))
            ->values()
            ->all();
    }

    protected static function normalizeLocalizedValue(mixed $value): array
    {
        $value = is_array($value) ? $value : ['us' => (string) $value];

        return [
            'id' => self::firstFilled($value['id'] ?? null, $value['us'] ?? null, $value['cn'] ?? null),
            'us' => self::firstFilled($value['us'] ?? null, $value['id'] ?? null, $value['cn'] ?? null),
            'cn' => self::firstFilled($value['cn'] ?? null, $value['us'] ?? null, $value['id'] ?? null),
        ];
    }

    protected static function firstFilled(mixed ...$values): string
    {
        foreach ($values as $value) {
            if (filled($value)) {
                return (string) $value;
            }
        }

        return '';
    }
}
