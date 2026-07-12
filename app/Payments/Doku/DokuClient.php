<?php

namespace App\Payments\Doku;

interface DokuClient
{
    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function createCheckoutPayment(array $payload, string $requestId): array;

    /**
     * @return array<string, mixed>
     */
    public function status(string $invoiceNumber, ?string $requestId = null): array;
}
