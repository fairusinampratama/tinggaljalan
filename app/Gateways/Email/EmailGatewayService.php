<?php

namespace App\Gateways\Email;

use App\Mail\BookingPaymentInvoiceMail;
use App\Mail\BookingPaymentReceiptMail;
use App\Models\BookingPayment;
use App\Models\EmailGatewaySetting;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class EmailGatewayService
{
    public function current(): ?EmailGatewaySetting
    {
        if (! Schema::hasTable('email_gateway_settings')) {
            return null;
        }

        return EmailGatewaySetting::query()->first();
    }

    public function sendInvoice(BookingPayment $payment): void
    {
        $payment->loadMissing('booking.tourPackage.destination');

        if (! in_array($payment->status, ['pending', 'invoice_sent'], true)) {
            throw new InvalidArgumentException('A payment request email can only be sent while payment is awaiting payment.');
        }

        if (! filled($payment->booking?->email)) {
            throw new InvalidArgumentException('Booking email is required before sending invoice email.');
        }

        if (in_array($payment->provider, ['midtrans', 'doku'], true) && ! filled($payment->snap_url)) {
            throw new InvalidArgumentException('A hosted payment URL is required before sending invoice email.');
        }

        $this->send($payment->booking->email, new BookingPaymentInvoiceMail($payment));
    }

    public function sendReceipt(BookingPayment $payment): void
    {
        $payment->loadMissing('booking.tourPackage.destination');

        if (! filled($payment->booking?->email)) {
            throw new InvalidArgumentException('Booking email is required before sending a payment receipt.');
        }

        if ($payment->status !== 'paid') {
            throw new InvalidArgumentException('A payment receipt can only be sent for a paid payment.');
        }

        $this->send($payment->booking->email, new BookingPaymentReceiptMail($payment));
    }

    public function sendTest(string $recipient): void
    {
        if (! filled($recipient)) {
            throw new InvalidArgumentException('A recipient email is required.');
        }

        $this->send($recipient, new TestGatewayMail());
    }

    public function send(string $recipient, mixed $mailable): void
    {
        $setting = $this->current();

        if (! $setting || ! $setting->is_enabled || $setting->provider === EmailGatewaySetting::PROVIDER_LOG) {
            $this->assertFallbackMailReady();
            Mail::to($recipient)->send($mailable);

            return;
        }

        if ($setting->provider !== EmailGatewaySetting::PROVIDER_SMTP) {
            throw new InvalidArgumentException('Unsupported email gateway provider.');
        }

        $this->assertSmtpReady($setting);
        $this->sendUsingSmtpSetting($setting, $recipient, $mailable);
    }

    public function recordTestResult(EmailGatewaySetting $setting, string $status, string $message): void
    {
        $setting->forceFill([
            'last_tested_at' => now(),
            'last_test_status' => $status,
            'last_test_message' => str($message)->limit(1000)->toString(),
        ])->save();
    }

    private function assertFallbackMailReady(): void
    {
        $mailer = (string) config('mail.default');

        if (in_array($mailer, ['log', 'array'], true)) {
            return;
        }

        $from = strtolower((string) config('mail.from.address'));

        if (! filled($from) || $from === 'hello@example.com') {
            throw new InvalidArgumentException('Configure a production MAIL_FROM_ADDRESS before sending email.');
        }
    }

    private function assertSmtpReady(EmailGatewaySetting $setting): void
    {
        foreach (['host', 'port', 'username', 'password', 'from_address', 'from_name'] as $field) {
            if (! filled($setting->{$field})) {
                throw new InvalidArgumentException("Email gateway {$field} is required for SMTP.");
            }
        }

        if (! in_array($setting->scheme, ['smtp', 'smtps'], true)) {
            throw new InvalidArgumentException('Email gateway scheme must be smtp or smtps.');
        }
    }

    private function sendUsingSmtpSetting(EmailGatewaySetting $setting, string $recipient, mixed $mailable): void
    {
        config([
            'mail.mailers.gateway_smtp' => [
                'transport' => 'smtp',
                'scheme' => $setting->scheme,
                'host' => $setting->host,
                'port' => $setting->port,
                'username' => $setting->username,
                'password' => $setting->password,
                'timeout' => null,
                'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST),
            ],
            'mail.from.address' => $setting->from_address,
            'mail.from.name' => $setting->from_name,
        ]);

        app(MailManager::class)->purge('gateway_smtp');
        Mail::mailer('gateway_smtp')->to($recipient)->send($mailable);
    }
}