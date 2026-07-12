<?php

namespace App\Jobs;

use App\Gateways\Email\EmailGatewayService;
use App\Gateways\WhatsApp\NewBookingAdminWhatsAppMessage;
use App\Gateways\WhatsApp\WhatsAppGatewayService;
use App\Mail\NewBookingAdminMail;
use App\Models\Booking;
use App\Models\NotificationSetting;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendNewBookingAdminNotification
{
    public function __construct(public int $bookingId)
    {
    }

    public function handle(
        EmailGatewayService $email,
        WhatsAppGatewayService $whatsApp,
        NewBookingAdminWhatsAppMessage $whatsAppMessage,
    ): void {
        $booking = Booking::query()->with('tourPackage.destination')->find($this->bookingId);

        if (! $booking) {
            return;
        }

        $settings = NotificationSetting::current();

        if (! $settings->is_enabled) {
            return;
        }

        $booking->forceFill([
            'admin_notification_attempted_at' => now(),
            'admin_notification_error' => null,
        ])->save();

        $errors = [];

        if ($settings->whatsapp_enabled && filled($settings->admin_whatsapp_number)) {
            try {
                $result = $whatsApp->sendMessageTo($settings->admin_whatsapp_number, $whatsAppMessage->text($booking));

                if ($result->sent) {
                    $booking->forceFill([
                        'admin_whatsapp_sent_at' => now(),
                        'admin_whatsapp_failed_at' => null,
                    ])->save();
                } else {
                    throw new \RuntimeException($result->error ?: 'WhatsApp notification was not sent.');
                }
            } catch (Throwable $exception) {
                $errors[] = 'WhatsApp: '.$exception->getMessage();
                $booking->forceFill([
                    'admin_whatsapp_failed_at' => now(),
                ])->save();
                Log::warning('New booking admin WhatsApp notification failed.', [
                    'booking_id' => $booking->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if ($settings->email_enabled && filled($settings->admin_email)) {
            try {
                $email->send($settings->admin_email, new NewBookingAdminMail($booking));
                $booking->forceFill([
                    'admin_email_sent_at' => now(),
                    'admin_email_failed_at' => null,
                ])->save();
            } catch (Throwable $exception) {
                $errors[] = 'Email: '.$exception->getMessage();
                $booking->forceFill([
                    'admin_email_failed_at' => now(),
                ])->save();
                Log::warning('New booking admin email notification failed.', [
                    'booking_id' => $booking->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if ($errors !== []) {
            $booking->forceFill([
                'admin_notification_error' => str(implode(' | ', $errors))->limit(2000)->toString(),
            ])->save();
        }
    }
}