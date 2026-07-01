<?php

namespace App\Gateways\WhatsApp;

use App\Models\WhatsappGatewaySetting;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class HttpWhatspieClient implements WhatspieClient
{
    public function __construct(private readonly WhatsappGatewaySettingsService $settings)
    {
    }

    public function sendMessage(string $to, string $message): array
    {
        $setting = $this->settings->current();

        if (! $setting) {
            throw new InvalidArgumentException('WhatsApp gateway settings are not available.');
        }

        foreach (['api_base_url', 'api_token', 'session_id'] as $field) {
            if (! filled($setting->{$field})) {
                throw new InvalidArgumentException("Whatspie {$field} is required before sending WhatsApp.");
            }
        }

        return Http::timeout($setting->timeout_seconds ?: 15)
            ->acceptJson()
            ->withToken($setting->api_token)
            ->post(rtrim((string) $setting->api_base_url, '/').'/messages', [
                'device' => preg_replace('/\\D+/', '', (string) $setting->session_id),
                'receiver' => $to,
                'type' => 'chat',
                'params' => [
                    'text' => $message,
                ],
                'simulate_typing' => 1,
            ])
            ->throw()
            ->json();
    }
}