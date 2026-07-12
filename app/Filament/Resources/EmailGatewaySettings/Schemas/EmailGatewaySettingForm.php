<?php

namespace App\Filament\Resources\EmailGatewaySettings\Schemas;

use App\Models\EmailGatewaySetting;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmailGatewaySettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Email gateway')
                ->description('Local can use log. Production can use Brevo SMTP or another SMTP provider.')
                ->schema([
                    Toggle::make('is_enabled')
                        ->label('Enabled')
                        ->default(true)
                        ->helperText('Enable or disable all outgoing email notifications.'),
                    Select::make('provider')
                        ->options([
                            EmailGatewaySetting::PROVIDER_LOG => 'Log / Laravel fallback',
                            EmailGatewaySetting::PROVIDER_SMTP => 'SMTP',
                        ])
                        ->required()
                        ->default(EmailGatewaySetting::PROVIDER_LOG)
                        ->helperText('Choose \'Log\' for local development or \'SMTP\' to send actual emails.'),
                    TextInput::make('host')
                        ->default('smtp-relay.brevo.com')
                        ->maxLength(255)
                        ->helperText('The outgoing SMTP server hostname (e.g., \'smtp-relay.brevo.com\').'),
                    TextInput::make('port')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(65535)
                        ->default(587)
                        ->helperText('The port used by the SMTP host (usually 587 for TLS, or 465 for SSL).'),
                    Select::make('scheme')
                        ->options(['smtp' => 'smtp', 'smtps' => 'smtps'])
                        ->default('smtp')
                        ->helperText('Security protocol. Use \'smtp\' for TLS/starttls or \'smtps\' for SSL.'),
                    TextInput::make('username')
                        ->label('SMTP Username')
                        ->maxLength(255)
                        ->helperText('The username/email credential for the SMTP service.'),
                    TextInput::make('password')
                        ->label('SMTP Password')
                        ->password()
                        ->revealable()
                        ->helperText('Leave blank on edit to keep the existing encrypted password.')
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->maxLength(2000),
                    TextInput::make('from_address')
                        ->email()
                        ->default('booking@tinggaljalan.com')
                        ->maxLength(255)
                        ->helperText('The sender email address appearing on sent emails.'),
                    TextInput::make('from_name')
                        ->default('Tinggal Jalan')
                        ->maxLength(255)
                        ->helperText('The sender display name appearing on sent emails.'),
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