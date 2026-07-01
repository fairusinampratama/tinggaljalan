<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class BookingLanguage
{
    public const DEFAULT = 'us';

    public const OPTIONS = [
        'id' => 'Bahasa Indonesia',
        'us' => 'English',
        'cn' => 'Simplified Chinese',
    ];

    public static function normalize(?string $language): string
    {
        return array_key_exists((string) $language, self::OPTIONS) ? (string) $language : self::DEFAULT;
    }

    public static function locale(?string $language): string
    {
        return match (self::normalize($language)) {
            'id' => 'id',
            'cn' => 'zh_CN',
            default => 'en',
        };
    }

    public static function label(?string $language): string
    {
        return self::OPTIONS[self::normalize($language)];
    }

    public static function translate(string $key, array $replace = [], ?string $language = null): string
    {
        return trans($key, $replace, self::locale($language));
    }

    public static function date(Carbon|string|null $date, ?string $language, bool $withTime = false): string
    {
        if (! $date) {
            return '-';
        }

        $date = Carbon::parse($date)->locale(self::locale($language));

        return $date->translatedFormat($withTime ? 'j M Y, H:i' : 'j F Y');
    }
}