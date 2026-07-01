<?php

namespace App\Filament\Resources\PaymentSettings\Pages;

use App\Filament\Resources\PaymentSettings\PaymentSettingResource;
use App\Models\PaymentSetting;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentSetting extends CreateRecord
{
    protected static string $resource = PaymentSettingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return array_merge(PaymentSetting::defaults(), $data, ['gateway' => PaymentSetting::GATEWAY_MIDTRANS]);
    }
}