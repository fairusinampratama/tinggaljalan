<?php

namespace App\Payments\ExchangeRates;

use App\Payments\PaymentSettingsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class ExchangeRateService
{
    private const CACHE_KEY = 'exchange-rates.usd-idr.latest';

    public function __construct(
        private readonly ExchangeRateClient $client,
        private readonly PaymentSettingsService $settings,
    ) {
    }

    public function usdToIdr(?int $manualRate = null): ExchangeRateQuote
    {
        if ($manualRate !== null) {
            if ($manualRate < 1) {
                throw new InvalidArgumentException('A positive USD to IDR exchange rate is required.');
            }

            return new ExchangeRateQuote(
                source: 'manual',
                rawRate: (float) $manualRate,
                bufferPercent: 0,
                finalRate: $manualRate,
                fetchedAt: now(),
                manualOverride: true,
            );
        }

        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached) && is_numeric($cached['raw_rate'] ?? null) && $this->isFresh($cached)) {
            return $this->quoteFromPayload($cached, true);
        }

        try {
            $payload = $this->client->usdToIdr();
            Cache::forever(self::CACHE_KEY, $payload);

            return $this->quoteFromPayload($payload);
        } catch (\Throwable $exception) {
            if (is_array($cached) && is_numeric($cached['raw_rate'] ?? null)) {
                return $this->quoteFromPayload($cached, true);
            }

            throw new InvalidArgumentException('Could not fetch USD to IDR rate. Enter a manual exchange rate to create this payment request.', previous: $exception);
        }
    }

    public function previewUsdToIdr(): ?ExchangeRateQuote
    {
        try {
            return $this->usdToIdr();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function quoteFromPayload(array $payload, bool $fromCache = false): ExchangeRateQuote
    {
        $rawRate = (float) $payload['raw_rate'];
        $buffer = $this->settings->exchangeRateBufferPercent();
        $finalRate = (int) ceil($rawRate * (1 + ($buffer / 100)));
        $fetchedAt = Carbon::parse((string) ($payload['fetched_at'] ?? now()->toIso8601String()));

        return new ExchangeRateQuote(
            source: (string) ($payload['source'] ?? $this->settings->exchangeRateProvider()),
            rawRate: $rawRate,
            bufferPercent: $buffer,
            finalRate: $finalRate,
            fetchedAt: $fetchedAt,
            fromCache: $fromCache,
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function isFresh(array $payload): bool
    {
        $fetchedAt = Carbon::parse((string) ($payload['fetched_at'] ?? now()->subYears(10)->toIso8601String()));
        $ttlHours = $this->settings->exchangeRateCacheTtlHours();

        return $fetchedAt->greaterThanOrEqualTo(now()->subHours($ttlHours));
    }
}