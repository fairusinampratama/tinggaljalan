<?php

namespace App\Filament\Resources\WhatsappGatewaySettings\Pages;

use App\Filament\Resources\WhatsappGatewaySettings\WhatsappGatewaySettingResource;
use App\Models\WhatsappGatewaySetting;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWhatsappGatewaySettings extends ListRecords
{
    protected static string $resource = WhatsappGatewaySettingResource::class;

    protected function getHeaderActions(): array
    {
        return WhatsappGatewaySetting::query()->exists() ? [] : [CreateAction::make()];
    }
}