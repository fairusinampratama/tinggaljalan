<?php

namespace App\Filament\Resources\EmailGatewaySettings;

use App\Filament\Resources\EmailGatewaySettings\Pages\CreateEmailGatewaySetting;
use App\Filament\Resources\EmailGatewaySettings\Pages\EditEmailGatewaySetting;
use App\Filament\Resources\EmailGatewaySettings\Pages\ListEmailGatewaySettings;
use App\Filament\Resources\EmailGatewaySettings\Pages\ViewEmailGatewaySetting;
use App\Filament\Resources\EmailGatewaySettings\Schemas\EmailGatewaySettingForm;
use App\Filament\Resources\EmailGatewaySettings\Schemas\EmailGatewaySettingInfolist;
use App\Filament\Resources\EmailGatewaySettings\Tables\EmailGatewaySettingsTable;
use App\Models\EmailGatewaySetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class EmailGatewaySettingResource extends Resource
{
    protected static ?string $model = EmailGatewaySetting::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static string|\UnitEnum|null $navigationGroup = 'Site Management';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Email Gateway Settings';

    public static function form(Schema $schema): Schema
    {
        return EmailGatewaySettingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EmailGatewaySettingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmailGatewaySettingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmailGatewaySettings::route('/'),
            'create' => CreateEmailGatewaySetting::route('/create'),
            'view' => ViewEmailGatewaySetting::route('/{record}'),
            'edit' => EditEmailGatewaySetting::route('/{record}/edit'),
        ];
    }
}