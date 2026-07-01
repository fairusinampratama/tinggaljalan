<?php

namespace App\Filament\Resources\WhatsappGatewaySettings\Schemas;

use App\Models\WhatsappGatewaySetting;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WhatsappGatewaySettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('WhatsApp gateway')
                ->description('Manual keeps the existing wa.me fallback. Whatspie sends messages through a session-based API.')
                ->schema([
                    Toggle::make('is_enabled')->label('Enabled')->default(true),
                    Select::make('provider')
                        ->options([
                            WhatsappGatewaySetting::PROVIDER_MANUAL => 'Manual wa.me fallback',
                            WhatsappGatewaySetting::PROVIDER_WHATSPIE => 'Whatspie',
                        ])
                        ->required()
                        ->default(WhatsappGatewaySetting::PROVIDER_MANUAL),
                    TextInput::make('api_base_url')->label('API base URL')->placeholder('https://...')->maxLength(255),
                    TextInput::make('api_token')
                        ->label('API token')
                        ->password()
                        ->revealable()
                        ->helperText('Leave blank on edit to keep the existing encrypted token.')
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->maxLength(2000),
                    TextInput::make('session_id')->label('Session / device ID')->maxLength(255),
                    TextInput::make('default_country_code')->default('62')->required()->maxLength(8),
                    TextInput::make('timeout_seconds')->numeric()->minValue(3)->maxValue(120)->default(15),
                    Toggle::make('manual_fallback_enabled')->label('Manual fallback enabled')->default(true),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Last test')
                ->schema([
                    TextInput::make('last_tested_at')->disabled()->dehydrated(false),
                    TextInput::make('last_test_status')->disabled()->dehydrated(false),
                    TextInput::make('last_test_message')->disabled()->dehydrated(false)->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}