<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['gateway', 'is_enabled', 'mode', 'public_key', 'secret_key', 'public_label', 'booking_note', 'usd_note', 'exchange_rate_provider', 'exchange_rate_buffer_percent', 'exchange_rate_cache_ttl_hours'])]
class PaymentSetting extends Model
{
    public const GATEWAY_MIDTRANS = 'midtrans';

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'public_key' => 'encrypted',
            'secret_key' => 'encrypted',
            'exchange_rate_buffer_percent' => 'float',
            'exchange_rate_cache_ttl_hours' => 'integer',
        ];
    }

    public static function defaults(): array
    {
        return [
            'gateway' => self::GATEWAY_MIDTRANS,
            'is_enabled' => true,
            'mode' => 'sandbox',
            'public_label' => 'Secure Midtrans payment link',
            'booking_note' => 'Payment is requested only after our team confirms availability.',
            'usd_note' => 'USD quotes are converted to IDR when payment is requested. Midtrans processes payments in IDR.',
            'exchange_rate_provider' => 'frankfurter',
            'exchange_rate_buffer_percent' => 2,
            'exchange_rate_cache_ttl_hours' => 12,
        ];
    }

    public static function midtrans(): self
    {
        return self::query()->firstOrCreate(
            ['gateway' => self::GATEWAY_MIDTRANS],
            self::defaults(),
        );
    }
}