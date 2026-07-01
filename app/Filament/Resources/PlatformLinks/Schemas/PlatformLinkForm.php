<?php

namespace App\Filament\Resources\PlatformLinks\Schemas;

use App\Filament\Support\AdminForm;
use App\Models\PlatformLink;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PlatformLinkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Platform link')
                    ->schema([
                        TextInput::make('name')->required()->maxLength(255),
                        TextInput::make('url')->url()->required()->maxLength(255),
                        AdminForm::imageUpload('logo', 'Logo', 'admin/platform-links/logos')
                            ->required()
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'])
                            ->maxSize(2048)
                            ->imagePreviewHeight('6rem')
                            ->helperText('Upload PNG, JPG, WebP, or SVG up to 2 MB. Transparent PNG/WebP works best.'),
                        TextInput::make('alt')
                            ->label('Alt text')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Describe the logo, for example: Traveloka logo.'),
                        TextInput::make('sort_order')->required()->numeric()->default(0),
                        Toggle::make('is_active')->required()
                            ->default(fn (): bool => PlatformLink::query()->active()->count() < PlatformLink::MAX_ACTIVE)
                            ->helperText('Only four platform links can be active at once.'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
