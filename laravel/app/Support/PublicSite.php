<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\NewsArticle;
use App\Models\PackageAvailability;
use App\Models\TourPackage;
use App\Models\Voucher;
use App\Payments\PaymentSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PublicSite
{
    public const LANGUAGES = ['id', 'us', 'cn'];

    public static function language(Request $request): string
    {
        $requested = $request->query('lang');

        if (in_array($requested, self::LANGUAGES, true)) {
            $request->session()->put('language', $requested);

            return $requested;
        }

        return $request->session()->get('language', 'us');
    }

    public static function localized(mixed $value, string $language, ?string $fallback = null): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (! is_array($value)) {
            return $fallback ?? '';
        }

        foreach ([$language, 'us', 'id', 'cn'] as $key) {
            if (filled($value[$key] ?? null)) {
                return (string) $value[$key];
            }
        }

        return $fallback ?? '';
    }

    public static function image(?string $path): string
    {
        $path = self::assetPath($path);

        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        return asset($path);
    }

    public static function assetPath(?string $path): string
    {
        if (! $path) {
            return '/images/hero-bromo.jpg';
        }

        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        if (Str::startsWith($path, 'storage/')) {
            return '/'.$path;
        }

        if (Str::startsWith($path, 'admin/')) {
            return '/storage/'.$path;
        }

        return '/'.ltrim($path, '/');
    }

    public static function setting(string $group, string $key, mixed $fallback = null): mixed
    {
        $setting = \App\Models\Setting::query()
            ->where('group', $group)
            ->where('key', $key)
            ->first();

        return $setting?->value ?? $fallback;
    }

    public static function whatsappBase(): string
    {
        $contact = self::setting('site', 'contact_details', []);
        $number = preg_replace('/\D+/', '', $contact['whatsapp'] ?? '6281234567890');

        return "https://wa.me/{$number}";
    }

    public static function whatsappUrl(array $lines): string
    {
        return self::whatsappBase().'?text='.rawurlencode(implode("\n", array_filter($lines)));
    }

    public static function formatMoney(int|float|null $amount, string $currency = 'IDR'): string
    {
        $amount = (float) ($amount ?? 0);

        if ($currency === 'USD') {
            return '$'.number_format($amount, 0);
        }

        return 'Rp'.number_format($amount, 0, ',', '.');
    }

    public static function activeVoucher(?string $code, string $currency): ?Voucher
    {
        if (! $code) {
            return null;
        }

        $voucher = Voucher::query()
            ->active()
            ->where('code', strtoupper($code))
            ->where(function ($query) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->first();

        if (! $voucher) {
            return null;
        }

        $allowed = $voucher->allowed_currencies;

        if ($voucher->currency && $voucher->currency !== $currency) {
            return null;
        }

        if (is_array($allowed) && $allowed !== [] && ! in_array($currency, $allowed, true)) {
            return null;
        }

        return $voucher;
    }

    public static function bookingDraft(Request $request, ?TourPackage $fallbackPackage = null): array
    {
        $package = $fallbackPackage ?? TourPackage::query()->active()->ordered()->first();

        return array_merge([
            'route' => $package?->slug,
            'date' => now()->addDays(9)->toDateString(),
            'pax' => 2,
            'pickup' => '',
            'traveler_type' => 'international',
            'currency' => 'USD',
            'add_ons' => [],
            'name' => '',
            'email' => '',
            'whatsapp' => '',
            'whatsapp_country' => 'ID',
            'voucher' => 'BROMO10',
            'notes' => '',
        ], $request->session()->get('booking_draft', []));
    }

    public static function packageFromDraft(array $draft): ?TourPackage
    {
        return TourPackage::query()
            ->with(['destination', 'packageAddOns', 'itineraryItems'])
            ->active()
            ->where('slug', $draft['route'] ?? null)
            ->first()
            ?? TourPackage::query()->with(['destination', 'packageAddOns', 'itineraryItems'])->active()->ordered()->first();
    }

    public static function availability(?TourPackage $package, ?string $date): array
    {
        if (! $package || ! $date) {
            return ['status' => 'available', 'seats_left' => null, 'reason' => null];
        }

        $availability = PackageAvailability::query()
            ->whereDate('date', $date)
            ->where(function ($query) use ($package) {
                $query->where('tour_package_id', $package->id)
                    ->orWhere('destination_id', $package->destination_id);
            })
            ->orderByRaw('tour_package_id is null')
            ->first();

        return [
            'status' => $availability?->status ?? 'available',
            'seats_left' => $availability?->seats_left,
            'reason' => $availability?->reason,
        ];
    }

    public static function bookingSummary(?TourPackage $package, array $draft): array
    {
        $currency = $draft['currency'] ?? 'USD';
        $pax = max(1, (int) ($draft['pax'] ?? 1));
        $base = $currency === 'USD' ? (int) $package?->base_price_usd : (int) $package?->base_price_idr;
        $selected = collect($draft['add_ons'] ?? []);
        $addOns = $package?->packageAddOns
            ->filter(fn ($packageAddOn) => $packageAddOn->is_active)
            ->filter(fn ($packageAddOn) => $selected->contains((string) $packageAddOn->id))
            ->values() ?? collect();

        $addOnTotal = $addOns->sum(function ($packageAddOn) use ($currency, $pax) {
            $price = $currency === 'USD' ? (int) $packageAddOn->price_usd : (int) $packageAddOn->price_idr;

            return $packageAddOn->pricing_type === 'per_pax' ? $price * $pax : $price;
        });
        $subtotal = ($base * $pax) + $addOnTotal;
        $voucher = self::activeVoucher($draft['voucher'] ?? null, $currency);
        $discount = 0;

        if ($voucher) {
            $discount = $voucher->discount_type === 'percent'
                ? (int) floor($subtotal * ((float) $voucher->discount_value / 100))
                : (int) $voucher->discount_value;
        }

        $total = max(0, $subtotal - $discount);

        $paymentSettings = app(PaymentSettingsService::class);

        return compact('currency', 'pax', 'base', 'addOns', 'subtotal', 'voucher', 'discount', 'total') + [
            'payment_gateway' => $paymentSettings->publicLabel(),
            'payment_note' => $paymentSettings->bookingNote(),
            'usd_payment_note' => $paymentSettings->usdNote(),
        ];
    }

    public static function bookingCode(): string
    {
        do {
            $code = 'TJ-'.now()->format('ymd').'-'.strtoupper(Str::random(4));
        } while (Booking::query()->where('booking_code', $code)->exists());

        return $code;
    }

    public static function relatedArticles(TourPackage $package, int $limit = 3): Collection
    {
        return NewsArticle::query()
            ->published()
            ->where(function ($query) use ($package) {
                $query->where('destination_id', $package->destination_id)
                    ->orWhereHas('tourPackages', fn ($related) => $related->whereKey($package->id));
            })
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }

    public static function routeJsonLd(TourPackage $package, string $language): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'TouristTrip',
            'name' => self::localized($package->title, $language),
            'description' => self::localized($package->excerpt, $language),
            'image' => self::image($package->cover_image),
            'url' => route('routes.show', $package->slug),
        ];
    }

    public static function articleJsonLd(NewsArticle $article, string $language): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => self::localized($article->title, $language),
            'description' => self::localized($article->excerpt, $language),
            'image' => self::image($article->cover_image),
            'datePublished' => optional($article->published_at)->toIso8601String(),
            'dateModified' => optional($article->content_updated_at ?? $article->updated_at)->toIso8601String(),
            'url' => route('news.show', $article->slug),
        ];
    }

    public static function dateLabel(Carbon|string|null $date): string
    {
        return $date ? Carbon::parse($date)->format('M d, Y') : '';
    }
}
