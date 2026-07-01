<?php

namespace App\Filament\Support;

use App\Models\Booking;

class BookingNextStep
{
    public static function label(Booking $booking): string
    {
        return self::state($booking)['label'];
    }

    public static function color(Booking $booking): string
    {
        return self::state($booking)['color'];
    }

    public static function summary(Booking $booking): string
    {
        return self::state($booking)['summary'];
    }

    /**
     * @return array{label: string, color: string, summary: string}
     */
    private static function state(Booking $booking): array
    {
        $payment = $booking->latestPayment;

        if (in_array($booking->status, ['cancelled', 'completed'], true)) {
            return self::make('Closed', 'gray', 'No active admin action needed.');
        }

        if (blank($booking->whatsapp) && blank($booking->email)) {
            return self::make('Add contact details', 'danger', 'No WhatsApp or email is saved.');
        }

        if (! $booking->tour_package_id || ! $booking->travel_date) {
            return self::make('Complete trip details', 'danger', 'Package or travel date is missing.');
        }

        if ($booking->status === 'new') {
            return self::make('Confirm availability', 'warning', self::withUpcomingNote($booking, 'Review the request and confirm whether the trip can run.'));
        }

        if ($booking->status === 'confirmed' && $booking->travel_date?->isPast()) {
            return self::make('Mark trip completed', 'gray', 'Travel date has passed; close the trip when service is done.');
        }

        if ($payment && $payment->status === 'expired') {
            return self::make('Create new payment or cancel', 'warning', 'Payment link expired; resend a new request or cancel the booking.');
        }

        if ($payment && in_array($payment->status, ['failed', 'cancelled'], true)) {
            return self::make('Review payment request', 'danger', 'Payment request is not usable; create a new request or cancel.');
        }

        if ($payment && $payment->status === 'paid') {
            return self::make('Prepare trip', 'success', self::withUpcomingNote($booking, 'Payment is received; keep booking confirmed until the trip is completed.'));
        }

        if ($booking->status === 'confirmed' && ! $payment) {
            return self::make('Create payment request', 'success', self::withUpcomingNote($booking, 'Availability is confirmed; generate the Midtrans payment link.'));
        }

        if ($payment && in_array($payment->status, ['pending', 'invoice_sent'], true)) {
            if (blank($payment->sent_at) && blank($payment->whatsapp_opened_at) && blank($payment->whatsapp_sent_at)) {
                return self::make('Send invoice or WhatsApp', 'warning', self::withUpcomingNote($booking, 'Payment link is ready but has not been handed off.'));
            }

            return self::make('Wait for payment / sync', 'info', self::withUpcomingNote($booking, 'Payment link was handed off; wait for customer payment or sync status.'));
        }

        return self::make('Review booking', 'gray', self::withUpcomingNote($booking, 'Check booking details and choose the next action.'));
    }

    /**
     * @return array{label: string, color: string, summary: string}
     */
    private static function make(string $label, string $color, string $summary): array
    {
        return compact('label', 'color', 'summary');
    }

    private static function withUpcomingNote(Booking $booking, string $summary): string
    {
        if ($booking->travel_date?->isBetween(now()->startOfDay(), now()->addDays(7)->endOfDay())) {
            return $summary.' Trip is within 7 days.';
        }

        return $summary;
    }
}