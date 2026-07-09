<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable(['tour_package_id', 'destination_id', 'date', 'end_date', 'is_open_ended', 'status', 'seats_left', 'reason', 'notes'])]
class PackageAvailability extends Model
{
    protected static function booted(): void
    {
        static::saving(function (PackageAvailability $availability): void {
            $hasPackage = filled($availability->tour_package_id);
            $hasDestination = filled($availability->destination_id);

            if ($hasPackage === $hasDestination) {
                throw ValidationException::withMessages([
                    'scope_type' => 'Choose exactly one availability scope: a destination or a specific package.',
                ]);
            }

            if (! in_array($availability->status, ['available', 'limited', 'booked', 'blocked'], true)) {
                throw ValidationException::withMessages(['status' => 'Choose a valid availability status.']);
            }

            if ($availability->status === 'limited') {
                if (! is_numeric($availability->seats_left) || (int) $availability->seats_left < 1) {
                    throw ValidationException::withMessages([
                        'seats_left' => 'Limited availability requires at least one seat.',
                    ]);
                }
            } else {
                $availability->seats_left = null;
            }

            $duplicate = static::query()
                ->when($hasPackage, fn ($query) => $query->where('tour_package_id', $availability->tour_package_id))
                ->when($hasDestination, fn ($query) => $query->where('destination_id', $availability->destination_id)->whereNull('tour_package_id'))
                ->when($availability->exists, fn ($query) => $query->whereKeyNot($availability->getKey()))
                ->where(function ($query) use ($availability): void {
                    if (! $availability->is_open_ended && $availability->end_date) {
                        $query->whereDate('date', '<=', $availability->end_date);
                    }

                    $query->where(function ($sub) use ($availability): void {
                        $sub->where('is_open_ended', true)
                            ->orWhereNull('end_date')
                            ->orWhereDate('end_date', '>=', $availability->date);
                    });
                })
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'date' => 'An availability rule overlaps with an existing rule for this scope.',
                ]);
            }
        });
    }
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'end_date' => 'date',
            'is_open_ended' => 'boolean',
            'seats_left' => 'integer',
        ];
    }

    public function tourPackage(): BelongsTo
    {
        return $this->belongsTo(TourPackage::class);
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }
}
