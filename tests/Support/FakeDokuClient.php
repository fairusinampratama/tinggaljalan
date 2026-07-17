<?php

namespace Tests\Support;

use App\Payments\Doku\DokuClient;

class FakeDokuClient implements DokuClient
{
    public array $createdPayloads = [];

    public array $requestIds = [];

    public array $statusInvoiceNumbers = [];

    public array $checkoutResponse = [
        'response' => [
            'payment' => [
                'token_id' => 'fake-doku-token',
                'url' => 'https://sandbox.doku.com/checkout/link/fake-doku-token',
            ],
            'headers' => [
                'request_id' => 'fake-request-id',
            ],
        ],
    ];

    public array $statusPayload = [
        'transaction' => [
            'status' => 'PENDING',
        ],
    ];

    public function createCheckoutPayment(array $payload, string $requestId): array
    {
        $this->createdPayloads[] = $payload;
        $this->requestIds[] = $requestId;

        return $this->checkoutResponse;
    }

    public function status(string $invoiceNumber, ?string $requestId = null): array
    {
        $this->statusInvoiceNumbers[] = $invoiceNumber;

        return $this->statusPayload;
    }
}
