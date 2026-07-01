<?php

namespace App\Filament\Resources\WhatsappGatewaySettings\Pages;

use App\Filament\Resources\WhatsappGatewaySettings\Pages\Concerns\HasWhatsappGatewayTestAction;
use App\Filament\Resources\WhatsappGatewaySettings\WhatsappGatewaySettingResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditWhatsappGatewaySetting extends EditRecord
{
    use HasWhatsappGatewayTestAction;

    protected static string $resource = WhatsappGatewaySettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->sendTestWhatsappAction(),
            ViewAction::make(),
        ];
    }
}