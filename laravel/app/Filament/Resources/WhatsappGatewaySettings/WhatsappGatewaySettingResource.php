<?php

namespace App\Filament\Resources\WhatsappGatewaySettings;

use App\Filament\Resources\WhatsappGatewaySettings\Pages\CreateWhatsappGatewaySetting;
use App\Filament\Resources\WhatsappGatewaySettings\Pages\EditWhatsappGatewaySetting;
use App\Filament\Resources\WhatsappGatewaySettings\Pages\ListWhatsappGatewaySettings;
use App\Filament\Resources\WhatsappGatewaySettings\Pages\ViewWhatsappGatewaySetting;
use App\Filament\Resources\WhatsappGatewaySettings\Schemas\WhatsappGatewaySettingForm;
use App\Filament\Resources\WhatsappGatewaySettings\Schemas\WhatsappGatewaySettingInfolist;
use App\Filament\Resources\WhatsappGatewaySettings\Tables\WhatsappGatewaySettingsTable;
use App\Models\WhatsappGatewaySetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class WhatsappGatewaySettingResource extends Resource
{
    protected static ?string $model = WhatsappGatewaySetting::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|\UnitEnum|null $navigationGroup = 'Site Management';

    protected static ?int $navigationSort = 11;

    protected static ?string $navigationLabel = 'WhatsApp Gateway Settings';

    public static function form(Schema $schema): Schema
    {
        return WhatsappGatewaySettingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return WhatsappGatewaySettingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WhatsappGatewaySettingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWhatsappGatewaySettings::route('/'),
            'create' => CreateWhatsappGatewaySetting::route('/create'),
            'view' => ViewWhatsappGatewaySetting::route('/{record}'),
            'edit' => EditWhatsappGatewaySetting::route('/{record}/edit'),
        ];
    }
}