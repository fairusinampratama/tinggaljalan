<?php

namespace App\Payments\Doku;

use App\Payments\PaymentSettingsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;

class HttpDokuClient implements DokuClient
{
    public function __construct(private readonly PaymentSettingsService $settings)
    {
    }

    public function createCheckoutPayment(array $payload, string $requestId): array
    {
        $target = '/checkout/v1/payment';
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);

        if ($body === false) {
            throw new InvalidArgumentException('Could not encode DOKU checkout payload.');
        }

        return Http::withHeaders($this->headers('POST', $target, $requestId, $body))
            ->acceptJson()
            ->withBody($body, 'application/json')
            ->post($this->apiBaseUrl().$target)
            ->throw()
            ->json();
    }

    public function status(string $invoiceNumber, ?string $requestId = null): array
    {
        $target = '/orders/v1/status/'.rawurlencode($invoiceNumber);

        return Http::withHeaders($this->headers('GET', $target, $requestId ?: (string) Str::uuid()))
            ->acceptJson()
            ->get($this->apiBaseUrl().$target)
            ->throw()
            ->json();
    }

    /**
     * @return array<string, string>
     */
    private function headers(string $method, string $target, string $requestId, ?string $body = null): array
    {
        $timestamp = now('UTC')->format('Y-m-d\TH:i:s\Z');

        return [
            'Client-Id' => $this->clientId(),
            'Request-Id' => $requestId,
            'Request-Timestamp' => $timestamp,
            'Signature' => $this->signature($method, $target, $requestId, $timestamp, $body),
        ];
    }

    private function signature(string $method, string $target, string $requestId, string $timestamp, ?string $body = null): string
    {
        $components = [
            'Client-Id:'.$this->clientId(),
            'Request-Id:'.$requestId,
            'Request-Timestamp:'.$timestamp,
            'Request-Target:'.$target,
        ];

        if (strtoupper($method) !== 'GET') {
            $components[] = 'Digest:'.$this->digest($body ?: '');
        }

        return 'HMACSHA256='.base64_encode(hash_hmac('sha256', implode("\n", $components), $this->secretKey(), true));
    }

    private function digest(string $body): string
    {
        return base64_encode(hash('sha256', $body, true));
    }

    private function clientId(): string
    {
        return $this->settings->dokuClientId();
    }

    private function secretKey(): string
    {
        return $this->settings->dokuSecretKey();
    }

    private function apiBaseUrl(): string
    {
        return $this->settings->dokuIsProduction()
            ? 'https://api.doku.com'
            : 'https://api-sandbox.doku.com';
    }
}
