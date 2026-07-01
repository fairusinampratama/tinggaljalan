<?php

namespace App\Payments\ExchangeRates;

use Illuminate\Support\Carbon;

readonly class ExchangeRateQuote
{
    public function __construct(
        public string $source,
        public float $rawRate,
        public float $bufferPercent,
        public int $finalRate,
        public Carbon $fetchedAt,
        public bool $fromCache = false,
        public bool $manualOverride = false,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        return [
            'source' => $this->source,
            'pair' => 'USD_IDR',
            'raw_rate' => $this->rawRate,
            'buffer_percent' => $this->bufferPercent,
            'final_rate' => $this->finalRate,
            'fetched_at' => $this->fetchedAt->toIso8601String(),
            'from_cache' => $this->fromCache,
            'manual_override' => $this->manualOverride,
        ];
    }
}