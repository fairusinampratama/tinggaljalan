<?php

namespace App\Filament\Resources\EmailGatewaySettings\Pages;

use App\Filament\Resources\EmailGatewaySettings\EmailGatewaySettingResource;
use App\Models\EmailGatewaySetting;
use Filament\Resources\Pages\CreateRecord;

class CreateEmailGatewaySetting extends CreateRecord
{
    protected static string $resource = EmailGatewaySettingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return array_merge(EmailGatewaySetting::defaults(), $data);
    }
}