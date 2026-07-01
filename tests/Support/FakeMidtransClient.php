<?php

namespace Tests\Support;

use App\Payments\Midtrans\MidtransClient;

class FakeMidtransClient implements MidtransClient
{
    public array $createdPayloads = [];

    public array $cancelledOrderIds = [];

    public array $statusOrderIds = [];

    public ?\Throwable $statusException = null;

    public array $snapResponse = [
        'token' => 'fake-snap-token',
        'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/fake-snap-token',
    ];

    public array $statusPayload = [
        'transaction_status' => 'pending',
    ];

    public function createSnapTransaction(array $payload): array
    {
        $this->createdPayloads[] = $payload;

        return $this->snapResponse;
    }

    public function status(string $orderId): array
    {
        $this->statusOrderIds[] = $orderId;

        if ($this->statusException) {
            throw $this->statusException;
        }

        return ['order_id' => $orderId] + $this->statusPayload;
    }

    public function cancel(string $orderId): array
    {
        $this->cancelledOrderIds[] = $orderId;

        return [
            'order_id' => $orderId,
            'transaction_status' => 'cancel',
        ];
    }
}