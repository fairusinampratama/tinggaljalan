<?php

namespace App\Payments\ExchangeRates;

interface ExchangeRateClient
{
    /**
     * @return array{source: string, raw_rate: float, fetched_at: string, payload?: array<string, mixed>}
     */
    public function usdToIdr(): array;
}