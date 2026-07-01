<?php

namespace App\Filament\Resources\EmailGatewaySettings\Pages;

use App\Filament\Resources\EmailGatewaySettings\EmailGatewaySettingResource;
use App\Filament\Resources\EmailGatewaySettings\Pages\Concerns\HasEmailGatewayTestAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEmailGatewaySetting extends ViewRecord
{
    use HasEmailGatewayTestAction;

    protected static string $resource = EmailGatewaySettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->sendTestEmailAction(),
            EditAction::make(),
        ];
    }
}