<?php

namespace App\Filament\Resources\EmailGatewaySettings\Pages;

use App\Filament\Resources\EmailGatewaySettings\EmailGatewaySettingResource;
use App\Models\EmailGatewaySetting;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmailGatewaySettings extends ListRecords
{
    protected static string $resource = EmailGatewaySettingResource::class;

    protected function getHeaderActions(): array
    {
        return EmailGatewaySetting::query()->exists() ? [] : [CreateAction::make()];
    }
}