<?php

namespace App\Gateways\WhatsApp;

class WhatsAppSendResult
{
    public function __construct(
        public readonly bool $sent,
        public readonly bool $manualFallback,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $providerMessageId = null,
        public readonly ?string $error = null,
        public readonly array $rawResponse = [],
    ) {
    }

    public static function sent(?string $providerMessageId = null, array $rawResponse = []): self
    {
        return new self(true, false, providerMessageId: $providerMessageId, rawResponse: $rawResponse);
    }

    public static function manual(string $redirectUrl, ?string $error = null, array $rawResponse = []): self
    {
        return new self(false, true, redirectUrl: $redirectUrl, error: $error, rawResponse: $rawResponse);
    }

    public static function failed(string $error, array $rawResponse = []): self
    {
        return new self(false, false, error: $error, rawResponse: $rawResponse);
    }
}