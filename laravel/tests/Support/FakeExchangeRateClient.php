<?php

namespace Tests\Support;

use App\Payments\ExchangeRates\ExchangeRateClient;
use RuntimeException;

class FakeExchangeRateClient implements ExchangeRateClient
{
    public int $calls = 0;

    public bool $shouldFail = false;

    /**
     * @var array{source: string, raw_rate: float, fetched_at: string, payload?: array<string, mixed>}
     */
    public array $payload = [
        'source' => 'frankfurter',
        'raw_rate' => 16500.0,
        'fetched_at' => '2026-06-25T00:00:00+00:00',
    ];

    public function usdToIdr(): array
    {
        $this->calls++;

        if ($this->shouldFail) {
            throw new RuntimeException('Exchange provider is unavailable.');
        }

        return $this->payload;
    }
}