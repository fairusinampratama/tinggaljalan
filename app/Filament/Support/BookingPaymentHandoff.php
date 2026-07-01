<?php

namespace App\Filament\Support;

use App\Models\BookingPayment;

class BookingPaymentHandoff
{
    public static function status(?BookingPayment $payment): string
    {
        if (! $payment) {
            return 'Not requested';
        }

        if ($payment->status === 'paid') {
            return self::receiptStatus($payment);
        }

        if (in_array($payment->status, ['expired', 'failed', 'cancelled'], true)) {
            return 'Closed';
        }

        $emailSent = filled($payment->sent_at);
        $whatsappSent = filled($payment->whatsapp_sent_at);
        $whatsappFailed = filled($payment->whatsapp_failed_at);
        $whatsappOpened = filled($payment->whatsapp_opened_at);

        return match (true) {
            $emailSent && $whatsappSent => 'Email + WA sent',
            $emailSent && $whatsappFailed => 'Email sent, WA failed',
            $whatsappSent => 'WA sent',
            $whatsappFailed => 'WA failed',
            $emailSent && $whatsappOpened => 'Email + WA opened',
            $emailSent => 'Email sent',
            $whatsappOpened => 'WA opened',
            default => 'Payment created',
        };
    }

    public static function color(?BookingPayment $payment): string
    {
        return match (self::status($payment)) {
            'Email + WA sent', 'Receipt sent', 'Paid' => 'success',
            'WA sent', 'Email + WA opened', 'Email sent', 'WA opened', 'Email receipt sent', 'WA confirmation sent' => 'info',
            'Payment created', 'Email sent, WA failed', 'WA failed', 'Receipt partially failed', 'Receipt not delivered' => 'warning',
            'Closed' => 'gray',
            default => 'gray',
        };
    }

    public static function summary(?BookingPayment $payment): string
    {
        if (! $payment) {
            return 'No payment request yet';
        }

        if ($payment->status === 'paid') {
            return self::receiptSummary($payment);
        }

        if (in_array($payment->status, ['expired', 'failed', 'cancelled'], true)) {
            return ucfirst($payment->status).' payment request';
        }

        $parts = [];

        if ($payment->sent_at) {
            $parts[] = 'Email sent '.$payment->sent_at->format('M d');
        }

        if ($payment->whatsapp_sent_at) {
            $parts[] = 'WA sent '.$payment->whatsapp_sent_at->format('M d');
        }

        if ($payment->whatsapp_failed_at) {
            $parts[] = 'WA failed '.$payment->whatsapp_failed_at->format('M d');
        }

        if ($payment->whatsapp_opened_at) {
            $parts[] = 'WA opened '.$payment->whatsapp_opened_at->format('M d');
        }

        return $parts ? implode(' / ', $parts) : 'Payment link ready, not handed off';
    }

    private static function receiptStatus(BookingPayment $payment): string
    {
        $emailSent = filled($payment->receipt_email_sent_at);
        $whatsappSent = filled($payment->receipt_whatsapp_sent_at);
        $failed = filled($payment->receipt_email_failed_at) || filled($payment->receipt_whatsapp_failed_at);

        return match (true) {
            $emailSent && $whatsappSent => 'Receipt sent',
            ($emailSent || $whatsappSent) && $failed => 'Receipt partially failed',
            $emailSent => 'Email receipt sent',
            $whatsappSent => 'WA confirmation sent',
            filled($payment->receipt_notifications_attempted_at) => 'Receipt not delivered',
            default => 'Paid',
        };
    }

    private static function receiptSummary(BookingPayment $payment): string
    {
        $parts = [$payment->paid_at ? 'Paid '.$payment->paid_at->format('M d') : 'Payment received'];

        if ($payment->receipt_email_sent_at) {
            $parts[] = 'receipt email sent';
        } elseif ($payment->receipt_email_failed_at) {
            $parts[] = 'receipt email failed';
        }

        if ($payment->receipt_whatsapp_sent_at) {
            $parts[] = 'WA confirmation sent';
        } elseif ($payment->receipt_whatsapp_opened_at) {
            $parts[] = 'manual WA opened';
        } elseif ($payment->receipt_whatsapp_failed_at) {
            $parts[] = 'WA confirmation failed';
        }

        return implode(' / ', $parts);
    }
}
