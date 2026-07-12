<?php

namespace App\Filament\Resources\NotificationSettings\Pages;

use App\Filament\Resources\NotificationSettings\NotificationSettingResource;
use App\Models\NotificationSetting;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Schema;

class ListNotificationSettings extends ListRecords
{
    protected static string $resource = NotificationSettingResource::class;

    protected function getHeaderActions(): array
    {
        if (! Schema::hasTable('notification_settings')) {
            return [];
        }

        return NotificationSetting::query()->exists() ? [] : [CreateAction::make()];
    }
}