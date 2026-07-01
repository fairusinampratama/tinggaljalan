<?php

namespace App\Filament\Resources\WhatsappGatewaySettings\Pages;

use App\Filament\Resources\WhatsappGatewaySettings\Pages\Concerns\HasWhatsappGatewayTestAction;
use App\Filament\Resources\WhatsappGatewaySettings\WhatsappGatewaySettingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewWhatsappGatewaySetting extends ViewRecord
{
    use HasWhatsappGatewayTestAction;

    protected static string $resource = WhatsappGatewaySettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->sendTestWhatsappAction(),
            EditAction::make(),
        ];
    }
}