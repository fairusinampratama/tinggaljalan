<?php

namespace App\Filament\Resources\WhatsappGatewaySettings\Pages;

use App\Filament\Resources\WhatsappGatewaySettings\WhatsappGatewaySettingResource;
use App\Models\WhatsappGatewaySetting;
use Filament\Resources\Pages\CreateRecord;

class CreateWhatsappGatewaySetting extends CreateRecord
{
    protected static string $resource = WhatsappGatewaySettingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return array_merge(WhatsappGatewaySetting::defaults(), $data);
    }
}