<?php

namespace App\Filament\Support;

use App\Support\PublicSite;
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

                return $file->storeAs($directory, Str::ulid().'.'.$extension, 'public') ?: null;
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
                    'id' => $state['id'][$index] ?? $state['us'][$index] ?? $state['cn'][$index] ?? '',
                    'us' => $state['us'][$index] ?? $state['id'][$index] ?? $state['cn'][$index] ?? '',
                    'cn' => $state['cn'][$index] ?? $state['us'][$index] ?? $state['id'][$index] ?? '',
                ])
                ->filter(fn (array $item): bool => filled($item['id']) || filled($item['us']) || filled($item['cn']))
                ->values()
                ->all();
        }

        return collect($state)
            ->map(fn ($item): array => is_array($item) ? [
                'id' => $item['id'] ?? $item['us'] ?? $item['cn'] ?? '',
                'us' => $item['us'] ?? $item['id'] ?? $item['cn'] ?? '',
                'cn' => $item['cn'] ?? $item['us'] ?? $item['id'] ?? '',
            ] : [
                'id' => (string) $item,
                'us' => (string) $item,
                'cn' => '',
            ])
            ->filter(fn (array $item): bool => filled($item['id']) || filled($item['us']) || filled($item['cn']))
            ->values()
            ->all();
    }
}
