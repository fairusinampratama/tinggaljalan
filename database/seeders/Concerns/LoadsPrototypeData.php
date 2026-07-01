<?php

namespace Database\Seeders\Concerns;

trait LoadsPrototypeData
{
    protected function prototypeData(): array
    {
        static $data = null;

        if ($data === null) {
            $data = json_decode(file_get_contents(database_path('seeders/data/prototype.json')), true, flags: JSON_THROW_ON_ERROR);
        }

        return $data;
    }

    protected function localized(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value) && array_is_list($value)) {
            return [
                'id' => $value,
                'us' => $value,
                'cn' => $value,
            ];
        }

        if (is_array($value)) {
            $id = $value['id'] ?? $value['en'] ?? $value['us'] ?? $value['cn'] ?? null;
            $us = $value['us'] ?? $value['en'] ?? $id;
            $cn = $value['cn'] ?? $id;

            return [
                'id' => $id,
                'us' => $us,
                'cn' => $cn,
            ];
        }

        return [
            'id' => $value,
            'us' => $value,
            'cn' => $value,
        ];
    }

    protected function pricingType(?string $pricing): string
    {
        return match ($pricing) {
            'perPax' => 'per_pax',
            default => 'per_booking',
        };
    }
}
