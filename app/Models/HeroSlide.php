<?php

namespace App\Models;

use App\Models\Concerns\HasTravelScopes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

#[Fillable([
    'admin_label',
    'desktop_image',
    'mobile_image',
    'image_alt',
    'eyebrow',
    'heading',
    'description',
    'primary_cta_label',
    'primary_cta_url',
    'secondary_cta_label',
    'secondary_cta_url',
    'text_alignment',
    'focal_position',
    'overlay_strength',
    'sort_order',
    'is_active',
    'start_date',
    'end_date',
])]
class HeroSlide extends Model
{
    use HasTravelScopes;

    protected function casts(): array
    {
        return [
            'image_alt' => 'array',
            'eyebrow' => 'array',
            'heading' => 'array',
            'description' => 'array',
            'primary_cta_label' => 'array',
            'secondary_cta_label' => 'array',
            'sort_order' => 'integer',
            'overlay_strength' => 'integer',
            'is_active' => 'boolean',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }


    public function displayLabel(): string
    {
        return $this->admin_label
            ?: data_get($this->heading, 'us')
            ?: "Hero slide #{$this->getKey()}";
    }

    public function publicationStatus(): string
    {
        if (! $this->is_active) {
            return 'Draft';
        }

        if ($this->start_date?->isFuture()) {
            return 'Upcoming';
        }

        if ($this->end_date?->isPast()) {
            return 'Expired';
        }

        if ($this->start_date || $this->end_date) {
            return 'Scheduled';
        }

        return 'Active';
    }

    public function scopeActiveScheduled(Builder $query): void
    {
        $now = now();
        $query->where('is_active', true)
              ->where(function (Builder $q) use ($now) {
                  $q->whereNull('start_date')->orWhere('start_date', '<=', $now);
              })
              ->where(function (Builder $q) use ($now) {
                  $q->whereNull('end_date')->orWhere('end_date', '>=', $now);
              });
    }
}
