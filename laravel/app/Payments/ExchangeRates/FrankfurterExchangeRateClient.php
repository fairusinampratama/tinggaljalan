<?php

namespace App\Payments\ExchangeRates;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class FrankfurterExchangeRateClient implements ExchangeRateClient
{
    public function usdToIdr(): array
    {
        $response = Http::timeout(5)->get('https://api.frankfurter.dev/v2/rate/USD/IDR')->throw();
        $payload = $response->json();
        $rate = data_get($payload, 'rate') ?? data_get($payload, 'rates.IDR');

        if (! is_numeric($rate) || (float) $rate <= 0) {
            throw new RuntimeException('Frankfurter did not return a usable USD to IDR rate.');
        }

        return [
            'source' => 'frankfurter',
            'raw_rate' => (float) $rate,
            'fetched_at' => now()->toIso8601String(),
            'payload' => is_array($payload) ? $payload : [],
        ];
    }
}