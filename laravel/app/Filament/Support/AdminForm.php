<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

class AdminForm
{
    public static function localized(string $field, string $label, bool $required = false, bool $textarea = false): Section
    {
        $component = $textarea ? Textarea::class : TextInput::class;

        return Section::make($label)
            ->schema([
                $component::make("{$field}.id")
                    ->label('Indonesian')
                    ->required($required),
                $component::make("{$field}.us")
                    ->label('English')
                    ->required($required),
                $component::make("{$field}.cn")
                    ->label('Chinese'),
            ])
            ->columns(3)
            ->columnSpanFull();
    }

    public static function localizedRepeater(string $field, string $label): Repeater
    {
        return Repeater::make($field)
            ->label($label)
            ->schema([
                TextInput::make('id')->label('Indonesian')->required(),
                TextInput::make('us')->label('English'),
                TextInput::make('cn')->label('Chinese'),
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
}
