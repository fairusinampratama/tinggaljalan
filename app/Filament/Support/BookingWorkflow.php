<?php

namespace App\Filament\Support;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Builder;

class BookingWorkflow
{
    public const NEEDS_ACTION = 'needs_action';

    public const AWAITING_PAYMENT = 'awaiting_payment';

    public const CONFIRMED_TRIPS = 'confirmed_trips';

    public const CLOSED = 'closed';

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

    public static function category(Booking $booking): string
    {
        return self::state($booking)['category'];
    }

    public static function applyCategory(Builder $query, string $category): Builder
    {
        return match ($category) {
            self::AWAITING_PAYMENT => self::applyAwaitingPayment($query),
            self::CONFIRMED_TRIPS => self::applyConfirmedTrips($query),
            self::CLOSED => $query->whereIn('status', ['cancelled', 'completed']),
            default => self::applyNeedsAction($query),
        };
    }

    /**
     * @return array{label: string, color: string, summary: string, category: string}
     */
    private static function state(Booking $booking): array
    {
        $payment = $booking->latestPayment;

        if (in_array($booking->status, ['cancelled', 'completed'], true)) {
            return self::make('Closed', 'gray', 'No active admin action needed.', self::CLOSED);
        }

        if (blank($booking->whatsapp) && blank($booking->email)) {
            return self::make('Add contact details', 'danger', 'No WhatsApp or email is saved.', self::NEEDS_ACTION);
        }

        if (! $booking->tour_package_id || ! $booking->travel_date) {
            return self::make('Complete trip details', 'danger', 'Package or travel date is missing.', self::NEEDS_ACTION);
        }

        if ($booking->status === 'new') {
            return self::make('Confirm availability', 'warning', self::withUpcomingNote($booking, 'Review the request and confirm availability.'), self::NEEDS_ACTION);
        }

        if ($booking->travel_date->lt(now()->startOfDay())) {
            return self::make('Mark trip completed', 'gray', 'Travel date has passed; close the trip when service is done.', self::NEEDS_ACTION);
        }

        if ($payment && in_array($payment->status, ['expired', 'failed', 'cancelled'], true)) {
            return self::make('Resolve payment issue', 'danger', 'Payment request is no longer usable; create a new request or cancel.', self::NEEDS_ACTION);
        }

        if ($payment?->status === 'paid' && self::hasReceiptFailure($payment)) {
            return self::make('Resolve payment issue', 'warning', self::withUpcomingNote($booking, 'Payment is received, but receipt delivery needs attention.'), self::NEEDS_ACTION);
        }

        if ($payment?->status === 'paid') {
            return self::make('Prepare trip', 'success', self::withUpcomingNote($booking, 'Payment is received; prepare pickup and final trip details.'), self::CONFIRMED_TRIPS);
        }

        if ($booking->status === 'confirmed' && ! $payment) {
            return self::make('Create payment request', 'success', self::withUpcomingNote($booking, 'Availability is confirmed; create the Midtrans payment link.'), self::NEEDS_ACTION);
        }

        if ($payment && in_array($payment->status, ['pending', 'invoice_sent'], true)) {
            if (! self::wasHandedOff($payment)) {
                return self::make('Send payment request', 'warning', self::withUpcomingNote($booking, 'Payment link is ready but has not been sent.'), self::NEEDS_ACTION);
            }

            return self::make('Awaiting payment', 'info', self::withUpcomingNote($booking, 'Payment link was sent; wait for payment or sync Midtrans.'), self::AWAITING_PAYMENT);
        }

        return self::make('Confirm availability', 'info', self::withUpcomingNote($booking, 'Review the request and confirm availability.'), self::NEEDS_ACTION);
    }

    private static function applyNeedsAction(Builder $query): Builder
    {
        return $query
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->whereNot(fn (Builder $query): Builder => self::awaitingPaymentConditions($query))
            ->whereNot(fn (Builder $query): Builder => self::confirmedTripConditions($query));
    }

    private static function applyAwaitingPayment(Builder $query): Builder
    {
        return self::awaitingPaymentConditions($query);
    }

    private static function applyConfirmedTrips(Builder $query): Builder
    {
        return self::confirmedTripConditions($query);
    }

    private static function awaitingPaymentConditions(Builder $query): Builder
    {
        return self::applyActiveCompleteTripConditions($query)
            ->whereHas('latestPayment', fn (Builder $query): Builder => $query
                ->whereIn('status', ['pending', 'invoice_sent'])
                ->where(function (Builder $query): void {
                    $query
                        ->whereNotNull('sent_at')
                        ->orWhereNotNull('whatsapp_sent_at')
                        ->orWhereNotNull('whatsapp_opened_at');
                }));
    }

    private static function confirmedTripConditions(Builder $query): Builder
    {
        return self::applyActiveCompleteTripConditions($query)
            ->whereHas('latestPayment', fn (Builder $query): Builder => $query
                ->where('status', 'paid')
                ->whereNull('receipt_email_failed_at')
                ->whereNull('receipt_whatsapp_failed_at'));
    }

    private static function applyActiveCompleteTripConditions(Builder $query): Builder
    {
        return $query
            ->where('status', 'confirmed')
            ->whereNotNull('tour_package_id')
            ->whereNotNull('travel_date')
            ->whereDate('travel_date', '>=', now()->toDateString())
            ->where(function (Builder $query): void {
                $query
                    ->where(fn (Builder $query): Builder => $query->whereNotNull('whatsapp')->where('whatsapp', '!=', ''))
                    ->orWhere(fn (Builder $query): Builder => $query->whereNotNull('email')->where('email', '!=', ''));
            });
    }

    private static function wasHandedOff(object $payment): bool
    {
        return filled($payment->sent_at)
            || filled($payment->whatsapp_sent_at)
            || filled($payment->whatsapp_opened_at);
    }

    private static function hasReceiptFailure(object $payment): bool
    {
        return filled($payment->receipt_email_failed_at)
            || filled($payment->receipt_whatsapp_failed_at);
    }

    /**
     * @return array{label: string, color: string, summary: string, category: string}
     */
    private static function make(string $label, string $color, string $summary, string $category): array
    {
        return compact('label', 'color', 'summary', 'category');
    }

    private static function withUpcomingNote(Booking $booking, string $summary): string
    {
        if ($booking->travel_date?->isBetween(now()->startOfDay(), now()->addDays(7)->endOfDay())) {
            return $summary.' Trip is within 7 days.';
        }

        return $summary;
    }
}
