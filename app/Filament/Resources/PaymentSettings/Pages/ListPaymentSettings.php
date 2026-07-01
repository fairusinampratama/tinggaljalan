<?php

namespace App\Filament\Resources\PaymentSettings\Pages;

use App\Filament\Resources\PaymentSettings\PaymentSettingResource;
use App\Models\PaymentSetting;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPaymentSettings extends ListRecords
{
    protected static string $resource = PaymentSettingResource::class;

    protected function getHeaderActions(): array
    {
        return PaymentSetting::query()->exists() ? [] : [CreateAction::make()];
    }
}