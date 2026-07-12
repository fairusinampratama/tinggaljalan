<?php

namespace App\Filament\Resources\NotificationSettings\Pages;

use App\Filament\Resources\NotificationSettings\NotificationSettingResource;
use App\Models\NotificationSetting;
use Filament\Resources\Pages\CreateRecord;

class CreateNotificationSetting extends CreateRecord
{
    protected static string $resource = NotificationSettingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return array_merge(NotificationSetting::defaults(), $data);
    }
}