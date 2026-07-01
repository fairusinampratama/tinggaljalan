<?php

namespace App\Payments\Midtrans;

interface MidtransClient
{
    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function createSnapTransaction(array $payload): array;

    /**
     * @return array<string, mixed>
     */
    public function status(string $orderId): array;

    /**
     * @return array<string, mixed>
     */
    public function cancel(string $orderId): array;
}
