<?php

namespace App\Payments;

use App\Gateways\Email\EmailGatewayService;
use App\Gateways\WhatsApp\PaymentReceiptWhatsAppMessage;
use App\Gateways\WhatsApp\WhatsappGatewaySettingsService;
use App\Gateways\WhatsApp\WhatspieClient;
use App\Models\BookingPayment;
use App\Models\WhatsappGatewaySetting;
use InvalidArgumentException;

class PaymentReceiptService
{
    public function __construct(
        private readonly EmailGatewayService $email,
        private readonly WhatsappGatewaySettingsService $whatsappSettings,
        private readonly WhatspieClient $whatspie,
        private readonly PaymentReceiptWhatsAppMessage $messages,
    ) {
    }

    public function sendAutomatically(BookingPayment $payment): BookingPayment
    {
        if ($payment->status !== 'paid') {
            return $payment;
        }

        $claimed = BookingPayment::query()
            ->whereKey($payment->getKey())
            ->whereNull('receipt_notifications_attempted_at')
            ->update(['receipt_notifications_attempted_at' => now()]);

        if (! $claimed) {
            return $payment->refresh();
        }

        $payment->refresh();
        $this->sendEmail($payment);
        $this->sendWhatsApp($payment);

        return $payment->refresh();
    }

    public function sendEmail(BookingPayment $payment): ReceiptDeliveryResult
    {
        $payment->loadMissing('booking.tourPackage.destination');

        if ($payment->status !== 'paid') {
            return ReceiptDeliveryResult::failed('A receipt can only be sent for a paid payment.');
        }

        if (! filled($payment->booking?->email)) {
            return $this->markEmailFailed($payment, 'Booking email is required before sending a payment receipt.');
        }

        try {
            $this->email->sendReceipt($payment);
        } catch (\Throwable $exception) {
            return $this->markEmailFailed($payment, $exception->getMessage());
        }

        $payment->forceFill([
            'receipt_email_sent_at' => now(),
            'receipt_email_failed_at' => null,
            'receipt_email_error' => null,
        ])->save();

        return ReceiptDeliveryResult::sent();
    }

    public function sendWhatsApp(BookingPayment $payment, bool $forceManual = false): ReceiptDeliveryResult
    {
        $payment->loadMissing('booking.tourPackage');

        if ($payment->status !== 'paid') {
            return ReceiptDeliveryResult::failed('A receipt can only be sent for a paid payment.');
        }

        if (! filled($payment->booking?->whatsapp)) {
            return $this->markWhatsappFailed($payment, 'Booking WhatsApp number is required before sending a payment confirmation.');
        }

        $setting = $this->whatsappSettings->current();

        if ($forceManual || ! $setting || ! $setting->is_enabled || $setting->provider === WhatsappGatewaySetting::PROVIDER_MANUAL) {
            $error = 'Automatic WhatsApp is unavailable. Open the manual receipt message.';
            $payment->forceFill([
                'receipt_whatsapp_opened_at' => $forceManual ? now() : $payment->receipt_whatsapp_opened_at,
                'receipt_whatsapp_failed_at' => now(),
                'receipt_whatsapp_error' => $error,
            ])->save();

            return ReceiptDeliveryResult::manual($this->messages->manualUrl($payment), $error);
        }

        if ($setting->provider !== WhatsappGatewaySetting::PROVIDER_WHATSPIE) {
            return $this->markWhatsappFailed($payment, 'Unsupported WhatsApp gateway provider.');
        }

        try {
            $response = $this->whatspie->sendMessage(
                $this->normalizePhone((string) $payment->booking->whatsapp, $setting->default_country_code ?: '62'),
                $this->messages->text($payment),
            );
        } catch (\Throwable $exception) {
            $result = $this->markWhatsappFailed($payment, $exception->getMessage());

            if ($setting->manual_fallback_enabled) {
                return ReceiptDeliveryResult::manual($this->messages->manualUrl($payment), $result->error);
            }

            return $result;
        }

        $messageId = data_get($response, 'id')
            ?? data_get($response, 'message_id')
            ?? data_get($response, 'data.id')
            ?? data_get($response, 'data.message_id');

        $payment->forceFill([
            'receipt_whatsapp_sent_at' => now(),
            'receipt_whatsapp_failed_at' => null,
            'receipt_whatsapp_error' => null,
            'receipt_whatsapp_provider_message_id' => filled($messageId) ? (string) $messageId : null,
            'receipt_whatsapp_raw_response' => $response,
        ])->save();

        return ReceiptDeliveryResult::sent(filled($messageId) ? (string) $messageId : null);
    }

    private function markEmailFailed(BookingPayment $payment, string $error): ReceiptDeliveryResult
    {
        $payment->forceFill([
            'receipt_email_failed_at' => now(),
            'receipt_email_error' => $error,
        ])->save();

        return ReceiptDeliveryResult::failed($error);
    }

    private function markWhatsappFailed(BookingPayment $payment, string $error): ReceiptDeliveryResult
    {
        $payment->forceFill([
            'receipt_whatsapp_failed_at' => now(),
            'receipt_whatsapp_error' => $error,
        ])->save();

        return ReceiptDeliveryResult::failed($error);
    }

    private function normalizePhone(string $phone, string $defaultCountryCode): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';
        $country = preg_replace('/\D+/', '', $defaultCountryCode) ?: '62';

        return str_starts_with($digits, '0') ? $country.substr($digits, 1) : $digits;
    }
}
