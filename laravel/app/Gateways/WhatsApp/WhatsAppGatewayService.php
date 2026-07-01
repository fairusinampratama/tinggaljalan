<?php

namespace App\Gateways\WhatsApp;

use App\Models\BookingPayment;
use App\Models\WhatsappGatewaySetting;
use InvalidArgumentException;

class WhatsAppGatewayService
{
    public function __construct(
        private readonly WhatsappGatewaySettingsService $settings,
        private readonly WhatspieClient $whatspie,
        private readonly PaymentRequestWhatsAppMessage $messages,
    ) {
    }

    public function sendPaymentRequest(BookingPayment $payment, bool $forceManual = false): WhatsAppSendResult
    {
        $payment->loadMissing('booking.tourPackage');

        if (! in_array($payment->status, ['pending', 'invoice_sent'], true)) {
            return WhatsAppSendResult::failed('A payment request WhatsApp can only be sent while payment is awaiting payment.');
        }

        $setting = $this->settings->current();

        if ($forceManual || ! $setting || ! $setting->is_enabled || $setting->provider === WhatsappGatewaySetting::PROVIDER_MANUAL) {
            return $this->markManualFallback($payment);
        }

        if ($setting->provider !== WhatsappGatewaySetting::PROVIDER_WHATSPIE) {
            return $this->markFailedOrFallback($payment, 'Unsupported WhatsApp gateway provider.', []);
        }

        try {
            $response = $this->whatspie->sendMessage(
                $this->normalizePhone((string) $payment->booking?->whatsapp, $setting->default_country_code ?: '62'),
                $this->messages->text($payment),
            );
        } catch (\Throwable $exception) {
            return $this->markFailedOrFallback($payment, $exception->getMessage(), ['local_error' => $exception->getMessage()]);
        }

        $messageId = data_get($response, 'id')
            ?? data_get($response, 'message_id')
            ?? data_get($response, 'data.id')
            ?? data_get($response, 'data.message_id');
        $messageId = filled($messageId) ? (string) $messageId : '';

        $payment->forceFill([
            'whatsapp_sent_at' => $payment->whatsapp_sent_at ?? now(),
            'whatsapp_failed_at' => null,
            'whatsapp_error' => null,
            'whatsapp_provider_message_id' => $messageId ?: $payment->whatsapp_provider_message_id,
            'whatsapp_raw_response' => $response,
        ])->save();

        return WhatsAppSendResult::sent($messageId ?: null, $response);
    }

    public function sendTest(string $phone, string $message = 'This is a Tinggal Jalan WhatsApp gateway test.'): WhatsAppSendResult
    {
        $setting = $this->settings->current();

        if (! $setting || ! $setting->is_enabled || $setting->provider !== WhatsappGatewaySetting::PROVIDER_WHATSPIE) {
            throw new InvalidArgumentException('Enable Whatspie before sending a test WhatsApp.');
        }

        $response = $this->whatspie->sendMessage($this->normalizePhone($phone, $setting->default_country_code ?: '62'), $message);
        $messageId = data_get($response, 'id')
            ?? data_get($response, 'message_id')
            ?? data_get($response, 'data.id')
            ?? data_get($response, 'data.message_id');

        return WhatsAppSendResult::sent(filled($messageId) ? (string) $messageId : null, $response);
    }

    public function recordTestResult(WhatsappGatewaySetting $setting, string $status, string $message): void
    {
        $setting->forceFill([
            'last_tested_at' => now(),
            'last_test_status' => $status,
            'last_test_message' => str($message)->limit(1000)->toString(),
        ])->save();
    }

    public function manualPaymentUrl(BookingPayment $payment): string
    {
        return $this->messages->manualUrl($payment);
    }

    private function markManualFallback(BookingPayment $payment, ?string $error = null, array $rawResponse = []): WhatsAppSendResult
    {
        $payment->forceFill([
            'whatsapp_opened_at' => $payment->whatsapp_opened_at ?? now(),
            'whatsapp_error' => $error ?: $payment->whatsapp_error,
            'whatsapp_raw_response' => $rawResponse ?: $payment->whatsapp_raw_response,
        ])->save();

        return WhatsAppSendResult::manual($this->manualPaymentUrl($payment), $error, $rawResponse);
    }

    private function markFailedOrFallback(BookingPayment $payment, string $error, array $rawResponse): WhatsAppSendResult
    {
        $setting = $this->settings->current();

        $payment->forceFill([
            'whatsapp_failed_at' => $payment->whatsapp_failed_at ?? now(),
            'whatsapp_error' => $error,
            'whatsapp_raw_response' => $rawResponse,
        ])->save();

        if ($setting?->manual_fallback_enabled) {
            return $this->markManualFallback($payment, $error, $rawResponse);
        }

        return WhatsAppSendResult::failed($error, $rawResponse);
    }

    private function normalizePhone(string $phone, string $defaultCountryCode): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';
        $country = preg_replace('/\D+/', '', $defaultCountryCode) ?: '62';

        if (str_starts_with($digits, '0')) {
            return $country.substr($digits, 1);
        }

        return $digits;
    }
}