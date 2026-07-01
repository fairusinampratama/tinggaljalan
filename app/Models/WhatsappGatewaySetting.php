<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['provider', 'is_enabled', 'api_base_url', 'api_token', 'session_id', 'default_country_code', 'timeout_seconds', 'manual_fallback_enabled', 'last_tested_at', 'last_test_status', 'last_test_message'])]
class WhatsappGatewaySetting extends Model
{
    public const PROVIDER_MANUAL = 'manual';
    public const PROVIDER_WHATSPIE = 'whatspie';

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'api_token' => 'encrypted',
            'timeout_seconds' => 'integer',
            'manual_fallback_enabled' => 'boolean',
            'last_tested_at' => 'datetime',
        ];
    }

    public static function defaults(): array
    {
        return [
            'provider' => self::PROVIDER_MANUAL,
            'is_enabled' => true,
            'default_country_code' => '62',
            'timeout_seconds' => 15,
            'manual_fallback_enabled' => true,
        ];
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate([], self::defaults());
    }
}