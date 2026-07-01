<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['provider', 'is_enabled', 'host', 'port', 'username', 'password', 'scheme', 'from_address', 'from_name', 'last_tested_at', 'last_test_status', 'last_test_message'])]
class EmailGatewaySetting extends Model
{
    public const PROVIDER_LOG = 'log';
    public const PROVIDER_SMTP = 'smtp';

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'port' => 'integer',
            'password' => 'encrypted',
            'last_tested_at' => 'datetime',
        ];
    }

    public static function defaults(): array
    {
        return [
            'provider' => self::PROVIDER_LOG,
            'is_enabled' => true,
            'host' => 'smtp-relay.brevo.com',
            'port' => 587,
            'scheme' => 'smtp',
            'from_address' => 'booking@tinggaljalan.com',
            'from_name' => 'Tinggal Jalan',
        ];
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate([], self::defaults());
    }
}