<?php

namespace App\Filament\Resources\EmailGatewaySettings\Pages;

use App\Filament\Resources\EmailGatewaySettings\EmailGatewaySettingResource;
use App\Filament\Resources\EmailGatewaySettings\Pages\Concerns\HasEmailGatewayTestAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEmailGatewaySetting extends EditRecord
{
    use HasEmailGatewayTestAction;

    protected static string $resource = EmailGatewaySettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->sendTestEmailAction(),
            ViewAction::make(),
        ];
    }
}