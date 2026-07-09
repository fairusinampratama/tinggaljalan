<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['gateway', 'is_enabled', 'mode', 'public_key', 'secret_key', 'public_label', 'booking_note', 'usd_note', 'exchange_rate_provider', 'exchange_rate_buffer_percent', 'exchange_rate_cache_ttl_hours', 'manual_bank_accounts'])]
class PaymentSetting extends Model
{
    public const GATEWAY_MIDTRANS = 'midtrans';
    public const GATEWAY_MANUAL = 'manual';

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'public_key' => 'encrypted',
            'secret_key' => 'encrypted',
            'exchange_rate_buffer_percent' => 'float',
            'exchange_rate_cache_ttl_hours' => 'integer',
            'manual_bank_accounts' => 'array',
        ];
    }

    public static function defaults(): array
    {
        return [
            'gateway' => self::GATEWAY_MIDTRANS,
            'is_enabled' => true,
            'mode' => 'sandbox',
            'public_label' => 'Secure Midtrans payment link',
            'booking_note' => 'You won\'t be charged yet. We will send a secure payment link once your booking is confirmed.',
            'usd_note' => 'International cards accepted. Final billing is securely processed in IDR.',
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

    protected static function booted(): void
    {
        static::saving(function (PaymentSetting $setting) {
            if ($setting->is_enabled) {
                PaymentSetting::where('id', '!=', $setting->id)->update(['is_enabled' => false]);
            }
        });
    }
}