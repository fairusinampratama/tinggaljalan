<?php

namespace App\Filament\Resources\NotificationSettings\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NotificationSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Admin notifications')
                ->description('Private owner alerts sent when a new public booking request is received.')
                ->schema([
                    Toggle::make('is_enabled')
                        ->label('Enabled')
                        ->default(true)
                        ->helperText('Enable or disable all new booking owner notifications.'),
                    Toggle::make('whatsapp_enabled')
                        ->label('WhatsApp enabled')
                        ->default(true)
                        ->helperText('Requires the WhatsApp gateway to be configured for automatic Whatspie sending.'),
                    TextInput::make('admin_whatsapp_number')
                        ->label('Admin WhatsApp number')
                        ->tel()
                        ->maxLength(32)
                        ->helperText('Use an international number such as +6281234567890.'),
                    Toggle::make('email_enabled')
                        ->label('Email enabled')
                        ->default(true),
                    TextInput::make('admin_email')
                        ->label('Admin email')
                        ->email()
                        ->maxLength(255),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}