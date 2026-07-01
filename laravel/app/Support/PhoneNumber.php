<?php

namespace App\Support;

use libphonenumber\PhoneNumberUtil;
use Propaganistas\LaravelPhone\PhoneNumber as LaravelPhoneNumber;
use Throwable;

class PhoneNumber
{
    public static function normalize(?string $value, string $country = 'ID'): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, '00')) {
            $value = '+'.substr($value, 2);
        }

        try {
            return (new LaravelPhoneNumber($value, self::normalizeCountry($country)))->formatE164();
        } catch (Throwable) {
            return '';
        }
    }

    public static function isValid(?string $value, ?string $country = null): bool
    {
        if (trim((string) $value) === '') {
            return false;
        }

        try {
            return (new LaravelPhoneNumber((string) $value, $country ? self::normalizeCountry($country) : null))
                ->lenient()
                ->isValid();
        } catch (Throwable) {
            return false;
        }
    }

    public static function detectCountry(?string $value): ?string
    {
        if (trim((string) $value) === '') {
            return null;
        }

        try {
            $country = (new LaravelPhoneNumber((string) $value))->getCountry();

            return is_string($country) && preg_match('/^[A-Z]{2}$/', $country) ? $country : null;
        } catch (Throwable) {
            return null;
        }
    }

    public static function countries(): array
    {
        $util = PhoneNumberUtil::getInstance();
        $countries = [];

        foreach ($util->getSupportedRegions() as $country) {
            $countries[$country] = sprintf('%s (+%d)', $country, $util->getCountryCodeForRegion($country));
        }

        asort($countries);

        return ['ID' => $countries['ID']] + $countries;
    }

    private static function normalizeCountry(string $country): string
    {
        $country = strtoupper(trim($country));

        if (preg_match('/^[A-Z]{2}$/', $country)) {
            return $country;
        }

        $digits = preg_replace('/\D+/', '', $country) ?: '';

        return PhoneNumberUtil::getInstance()->getRegionCodeForCountryCode((int) ($digits ?: 62)) ?: 'ID';
    }
}
