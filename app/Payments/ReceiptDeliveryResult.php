<?php

namespace App\Payments;

final readonly class ReceiptDeliveryResult
{
    public function __construct(
        public bool $success,
        public bool $manualFallback = false,
        public ?string $redirectUrl = null,
        public ?string $providerMessageId = null,
        public ?string $error = null,
    ) {
    }

    public static function sent(?string $providerMessageId = null): self
    {
        return new self(true, providerMessageId: $providerMessageId);
    }

    public static function manual(string $url, string $error): self
    {
        return new self(false, true, $url, error: $error);
    }

    public static function failed(string $error): self
    {
        return new self(false, error: $error);
    }
}
