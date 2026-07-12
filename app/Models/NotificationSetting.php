<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

#[Fillable(['is_enabled', 'whatsapp_enabled', 'admin_whatsapp_number', 'email_enabled', 'admin_email'])]
class NotificationSetting extends Model
{
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'whatsapp_enabled' => 'boolean',
            'email_enabled' => 'boolean',
        ];
    }

    public static function defaults(): array
    {
        return [
            'is_enabled' => true,
            'whatsapp_enabled' => true,
            'email_enabled' => true,
        ];
    }

    public static function current(): self
    {
        if (! Schema::hasTable('notification_settings')) {
            return new self(array_merge(self::defaults(), ['is_enabled' => false]));
        }

        return self::query()->firstOrCreate([], self::defaults());
    }
}