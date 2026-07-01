<?php

namespace App\Filament\Support;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class BookingAttention
{
    public const ACTIONABLE_STATUSES = ['new', 'confirmed'];

    public static function status(Booking $booking): string
    {
        if (in_array($booking->status, ['cancelled', 'completed'], true)) {
            return 'Closed';
        }

        if (blank($booking->whatsapp) && blank($booking->email)) {
            return 'Missing contact';
        }

        if (! $booking->tour_package_id || ! $booking->travel_date) {
            return 'Missing trip';
        }

        if ($booking->status === 'new') {
            return 'Needs review';
        }

        if (self::isUpcomingSoon($booking)) {
            return 'Upcoming soon';
        }

        return 'Ready';
    }

    public static function color(Booking $booking): string
    {
        return match (self::status($booking)) {
            'Needs review', 'Upcoming soon' => 'warning',
            'Missing contact', 'Missing trip' => 'danger',
            'Ready' => 'success',
            'Closed' => 'gray',
            default => 'gray',
        };
    }

    public static function summary(Booking $booking): string
    {
        return match (self::status($booking)) {
            'Needs review' => 'New booking needs availability review',
            'Missing contact' => 'No WhatsApp or email saved',
            'Missing trip' => 'Missing package or travel date',
            'Upcoming soon' => 'Trip is within the next 7 days',
            'Ready' => 'Follow-up is on track',
            'Closed' => 'No active follow-up needed',
            default => 'Review booking details',
        };
    }

    public static function applyNeedsAttention(Builder $query): Builder
    {
        return $query
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->where(function (Builder $query): void {
                $query
                    ->where('status', 'new')
                    ->orWhere(function (Builder $query): void {
                        $query
                            ->where(fn (Builder $query): Builder => $query->whereNull('whatsapp')->orWhere('whatsapp', ''))
                            ->where(fn (Builder $query): Builder => $query->whereNull('email')->orWhere('email', ''));
                    })
                    ->orWhereNull('tour_package_id')
                    ->orWhereNull('travel_date')
                    ->orWhere(function (Builder $query): void {
                        self::applyUpcomingSoon($query);
                    });
            });
    }

    public static function applyUpcomingSoon(Builder $query): Builder
    {
        return $query
            ->where('status', 'confirmed')
            ->whereDate('travel_date', '>=', now()->toDateString())
            ->whereDate('travel_date', '<=', now()->addDays(7)->toDateString());
    }

    public static function transitionTo(Booking $booking, string $status): void
    {
        $timestampColumn = match ($status) {
            'confirmed' => 'confirmed_at',
            'cancelled' => 'cancelled_at',
            'completed' => 'completed_at',
            default => null,
        };

        if (! in_array($status, ['confirmed', 'cancelled', 'completed'], true)) {
            throw new InvalidArgumentException("Unsupported booking transition: {$status}");
        }

        $updates = ['status' => $status];

        if ($timestampColumn && blank($booking->{$timestampColumn})) {
            $updates[$timestampColumn] = now();
        }

        $booking->update($updates);
    }

    private static function isUpcomingSoon(Booking $booking): bool
    {
        return $booking->status === 'confirmed'
            && $booking->travel_date
            && $booking->travel_date->isBetween(now()->startOfDay(), now()->addDays(7)->endOfDay());
    }
}
