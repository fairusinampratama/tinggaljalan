<?php

namespace App\Gateways\WhatsApp;

use App\Models\WhatsappGatewaySetting;
use Illuminate\Support\Facades\Schema;

class WhatsappGatewaySettingsService
{
    public function current(): ?WhatsappGatewaySetting
    {
        if (! Schema::hasTable('whatsapp_gateway_settings')) {
            return null;
        }

        return WhatsappGatewaySetting::query()->first();
    }
}