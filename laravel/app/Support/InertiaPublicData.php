<?php

namespace App\Support;

use App\Models\AddOn;
use App\Models\ArticleCategory;
use App\Models\Destination;
use App\Models\Faq;
use App\Models\NewsArticle;
use App\Models\PackageAvailability;
use App\Models\PlatformLink;
use App\Models\Review;
use App\Models\TourPackage;
use App\Models\TrustStat;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class InertiaPublicData
{
    public static function shared(Request $request): array
    {
        $language = PublicSite::language($request);
        $packages = TourPackage::query()
            ->with(['destination', 'addOns', 'itineraryItems', 'newsArticles'])
            ->active()
            ->ordered()
            ->get();
        $articles = NewsArticle::query()
            ->with(['destination', 'articleCategory', 'tourPackages'])
            ->published()
            ->latest('published_at')
            ->get();

        return [
            'language' => $language,
            'site' => self::site(),
            'home' => self::home(),
            'bookingOptions' => self::bookingOptions($request),
            'routes' => self::routes($packages),
            'destinations' => Destination::query()->active()->ordered()->get()->map(fn (Destination $destination) => self::destination($destination))->values(),
            'articles' => self::articles($articles),
            'categories' => ArticleCategory::query()->active()->ordered()->get()->map(fn (ArticleCategory $category) => [
                'value' => $category->slug,
                'label' => self::localizedArray($category->label),
            ])->values(),
            'routeStyles' => self::routeStyles($packages),
            'faqs' => Faq::query()->active()->ordered()->get()->map(fn (Faq $faq) => [
                'question' => self::localizedArray($faq->question),
                'answer' => self::localizedArray($faq->answer),
                'placement' => $faq->placement,
            ])->values(),
            'reviews' => Review::query()->active()->featured()->ordered()->get()->map(fn (Review $review) => [
                'name' => $review->name,
                'origin' => self::localizedArray($review->origin),
                'rating' => (float) $review->rating,
                'text' => self::localizedArray($review->text),
                'source' => self::localizedArray($review->source),
            ])->values(),
            'trustStats' => TrustStat::query()->active()->ordered()->get()->map(fn (TrustStat $stat) => [
                'title' => self::localizedArray($stat->title),
                'value' => self::localizedArray($stat->value),
                'icon' => $stat->icon_key,
            ])->values(),
            'platformLinks' => PlatformLink::query()->active()->ordered()->get()->map(fn (PlatformLink $platform) => [
                'name' => $platform->name,
                'url' => $platform->url,
                'logo' => self::assetPath($platform->logo),
                'alt' => $platform->alt,
            ])->values(),
            'whatsappUrl' => PublicSite::whatsappUrl(['Halo Tinggal Jalan, saya ingin konsultasi rute perjalanan.']),
        ];
    }

    public static function site(): array
    {
        $contact = PublicSite::setting('site', 'contact_details', []);

        return [
            'logoUrl' => self::assetPath(PublicSite::setting('site', 'logo_url', '/images/logo-tj.png')),
            'trustBadges' => PublicSite::setting('site', 'trust_badges', []),
            'contactDetails' => $contact,
            'whatsappNumber' => preg_replace('/\D+/', '', PublicSite::setting('site', 'whatsapp_number', $contact['whatsapp'] ?? '6281234567890')),
            'whatsappBaseUrl' => PublicSite::whatsappBase(),
        ];
    }

    public static function home(): array
    {
        return [
            'whyChooseItems' => collect(PublicSite::setting('homepage', 'why_choose_items', []))->map(fn ($item) => [
                'title' => self::localizedArray($item['title'] ?? []),
                'text' => self::localizedArray($item['text'] ?? []),
                'icon' => $item['icon'] ?? 'compass',
            ])->values()->all(),
        ];
    }

    public static function bookingOptions(Request $request): array
    {
        return [
            'destinationOptions' => Destination::query()->active()->ordered()->pluck('name')->values()->all(),
            'paxOptions' => self::settingList('booking', 'pax_options'),
            'travelerTypeOptions' => self::settingList('booking', 'traveler_type_options'),
            'currencyOptions' => self::settingList('booking', 'currency_options'),
            'initialBooking' => PublicSite::bookingDraft($request),
        ];
    }

    public static function destination(Destination $destination): array
    {
        return [
            'id' => $destination->slug,
            'slug' => $destination->slug,
            'name' => $destination->name,
            'region' => $destination->region ?? $destination->province,
            'province' => $destination->province,
            'copy' => self::localizedArray($destination->short_description),
            'description' => self::localizedArray($destination->description),
            'image' => self::assetPath($destination->cover_image),
            'gallery' => collect($destination->gallery ?? [])->map(fn ($path) => self::assetPath($path))->values()->all(),
        ];
    }

    public static function route(TourPackage $package): array
    {
        $destination = $package->destination;
        $itinerary = $package->itineraryItems->map(function ($item) {
            $time = $item->time_label ? "{$item->time_label} - " : '';

            return [
                'id' => $time.PublicSite::localized($item->title, 'id'),
                'us' => $time.PublicSite::localized($item->title, 'us').($item->description ? ': '.PublicSite::localized($item->description, 'us') : ''),
                'id' => $time.PublicSite::localized($item->title, 'id').($item->description ? ': '.PublicSite::localized($item->description, 'id') : ''),
                'cn' => $time.PublicSite::localized($item->title, 'cn').($item->description ? ': '.PublicSite::localized($item->description, 'cn') : ''),
            ];
        })->values()->all();

        $priceNote = self::localizedArray($package->price_note ?: [
            'id' => 'Harga masih estimasi sampai jadwal dan kebutuhan grup dikonfirmasi.',
            'us' => 'Estimated price until schedule and group needs are confirmed.',
            'cn' => '价格为预估，需确认日期和团队需求。',
        ]);

        return [
            'id' => $package->slug,
            'slug' => $package->slug,
            'destinationId' => $destination?->slug,
            'destinationName' => [
                'id' => $destination?->name,
                'us' => $destination?->name,
                'cn' => $destination?->name,
            ],
            'title' => self::localizedArray($package->title),
            'category' => self::localizedArray($package->category),
            'tag' => self::localizedArray($package->tag ?? $package->category),
            'badge' => self::localizedArray($package->tag ?? $package->category),
            'excerpt' => self::localizedArray($package->excerpt),
            'intro' => self::localizedArray($package->intro ?? $package->excerpt),
            'why' => self::localizedArray($package->best_for ?? $package->intro ?? $package->excerpt),
            'bestFor' => self::localizedArray($package->best_for ?? $package->excerpt),
            'duration' => $package->duration,
            'difficulty' => PublicSite::localized($package->difficulty, 'us', 'Easy'),
            'basePrice' => $package->base_price_idr,
            'basePriceIdr' => $package->base_price_idr,
            'basePriceUsd' => $package->base_price_usd,
            'priceNote' => $priceNote,
            'image' => self::assetPath($package->cover_image),
            'imageAlt' => self::localizedArray($package->cover_alt),
            'gallery' => collect($package->gallery ?? [])->map(fn ($path) => self::assetPath($path))->values()->all(),
            'pickupAreas' => $package->pickup_areas ?? [],
            'pickupLabel' => self::localizedArray($package->pickup_label),
            'groupType' => self::localizedArray($package->group_type),
            'operator' => ['id' => 'Tim lokal', 'us' => 'Local team', 'cn' => '当地团队'],
            'highlights' => self::localizedList($package->highlights),
            'includes' => self::localizedList($package->includes),
            'excludes' => self::localizedList($package->excludes),
            'notes' => self::localizedList($package->notes),
            'details' => self::localizedList($package->details),
            'goodToKnow' => self::localizedList($package->good_to_know),
            'pickupDetails' => self::localizedList($package->pickup_areas),
            'itinerary' => $itinerary,
            'policies' => self::localizedObject($package->policies),
            'testimonials' => collect($package->testimonials ?? [])->map(fn ($item) => [
                'name' => $item['name'] ?? 'Traveler',
                'meta' => self::localizedArray($item['meta'] ?? $package->review_source),
                'quote' => self::localizedArray($item['quote'] ?? $item['text'] ?? []),
            ])->values()->all(),
            'addOns' => $package->addOns->map(fn (AddOn $addOn) => self::addOn($addOn))->values()->all(),
            'packageOptions' => [[
                'id' => $package->slug,
                'title' => self::localizedArray($package->title),
                'description' => self::localizedArray($package->intro ?? $package->excerpt),
                'basePriceIdr' => $package->base_price_idr,
                'basePriceUsd' => $package->base_price_usd,
                'pickupLabel' => self::localizedArray($package->pickup_label),
                'groupType' => self::localizedArray($package->group_type),
            ]],
            'rating' => (float) ($package->rating ?? 5),
            'reviewCount' => (int) ($package->review_count ?? 0),
            'reviewSource' => self::localizedArray($package->review_source),
            'styles' => $package->styles ?? [],
            'featured' => (bool) $package->is_featured,
            'relatedArticleSlugs' => $package->newsArticles->pluck('slug')->values()->all(),
            'availabilityByDate' => self::availabilityByDate($package),
        ];
    }

    public static function routes(Collection $packages): array
    {
        return $packages->map(fn (TourPackage $package) => self::route($package))->values()->all();
    }

    public static function article(NewsArticle $article): array
    {
        return [
            'id' => $article->slug,
            'slug' => $article->slug,
            'title' => self::localizedArray($article->title),
            'excerpt' => self::localizedArray($article->excerpt),
            'category' => $article->articleCategory?->slug,
            'destinationId' => $article->destination?->slug,
            'destinationName' => $article->destination?->name,
            'publishedDate' => optional($article->published_at)->toDateString(),
            'updatedDate' => optional($article->content_updated_at ?? $article->updated_at)->toDateString(),
            'readingTime' => self::localizedArray($article->reading_time),
            'coverImage' => self::assetPath($article->cover_image),
            'coverAlt' => self::localizedArray($article->cover_alt),
            'tags' => $article->tags ?? [],
            'seo' => self::localizedObject($article->seo),
            'sections' => collect($article->sections ?? [])->map(fn ($section) => [
                'heading' => self::localizedArray($section['heading'] ?? []),
                'body' => self::localizedArray($section['body'] ?? []),
            ])->values()->all(),
            'relatedRouteIds' => $article->tourPackages->pluck('slug')->values()->all(),
            'isFeatured' => (bool) $article->is_featured,
            'publishedTime' => optional($article->published_at)->toIso8601String(),
            'modifiedTime' => optional($article->content_updated_at ?? $article->updated_at)->toIso8601String(),
        ];
    }

    public static function articles(Collection $articles): array
    {
        return $articles->map(fn (NewsArticle $article) => self::article($article))->values()->all();
    }

    public static function addOn(AddOn $addOn): array
    {
        return [
            'id' => $addOn->slug,
            'slug' => $addOn->slug,
            'title' => self::localizedArray($addOn->title),
            'description' => self::localizedArray($addOn->description),
            'priceIdr' => $addOn->price_idr,
            'priceUsd' => $addOn->price_usd,
            'pricing' => $addOn->pricing_type === 'per_pax' ? 'perPax' : 'perBooking',
        ];
    }

    public static function bookingPayload(Request $request, ?TourPackage $package, array $draft): array
    {
        $summary = PublicSite::bookingSummary($package, $draft);

        return [
            'draft' => [
                'route' => $draft['route'] ?? $package?->slug,
                'date' => $draft['date'] ?? now()->addDays(9)->toDateString(),
                'pax' => (int) ($draft['pax'] ?? 2),
                'pickup' => $draft['pickup'] ?? '',
                'travelerType' => $draft['traveler_type'] ?? $draft['travelerType'] ?? 'international',
                'currency' => $draft['currency'] ?? 'USD',
                'addOns' => $draft['add_ons'] ?? $draft['addOns'] ?? [],
                'name' => $draft['name'] ?? '',
                'email' => $draft['email'] ?? '',
                'whatsapp' => $draft['whatsapp'] ?? '',
                'voucher' => $draft['voucher'] ?? '',
                'notes' => $draft['notes'] ?? '',
            ],
            'summary' => [
                'currency' => $summary['currency'],
                'pax' => $summary['pax'],
                'base' => $summary['base'],
                'basePrice' => $summary['base'],
                'subtotal' => $summary['subtotal'],
                'discount' => $summary['discount'],
                'total' => $summary['total'],
                'paymentGateway' => $summary['payment_gateway'],
                'voucher' => $summary['voucher'] ? [
                    'code' => $summary['voucher']->code,
                    'label' => $summary['voucher']->label,
                ] : null,
                'addOns' => $summary['addOns']->map(function (AddOn $addOn) use ($summary) {
                    $addOnPayload = self::addOn($addOn);
                    $unitPrice = $summary['currency'] === 'USD' ? (int) $addOn->price_usd : (int) $addOn->price_idr;
                    $quantity = $addOn->pricing_type === 'per_pax' ? $summary['pax'] : 1;

                    return $addOnPayload + [
                        'unitPrice' => $unitPrice,
                        'quantity' => $quantity,
                        'total' => $unitPrice * $quantity,
                    ];
                })->values()->all(),
                'addOnsTotal' => $summary['addOns']->sum(function (AddOn $addOn) use ($summary) {
                    $unitPrice = $summary['currency'] === 'USD' ? (int) $addOn->price_usd : (int) $addOn->price_idr;

                    return $addOn->pricing_type === 'per_pax' ? $unitPrice * $summary['pax'] : $unitPrice;
                }),
            ],
            'availability' => self::availabilityPayload(PublicSite::availability($package, $draft['date'] ?? null)),
        ];
    }

    private static function assetPath(?string $path): string
    {
        if (! $path) {
            return '/images/hero-bromo.jpg';
        }

        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        return '/'.ltrim($path, '/');
    }

    private static function settingList(string $group, string $key): array
    {
        return collect(PublicSite::setting($group, $key, []))->map(function ($item) {
            if (! is_array($item)) {
                return $item;
            }

            return collect($item)->mapWithKeys(function ($value, $field) {
                $localizedFields = ['label', 'meta', 'title', 'text', 'description'];

                return [$field => in_array($field, $localizedFields, true) ? self::localizedArray($value) : $value];
            })->all();
        })->values()->all();
    }

    private static function routeStyles(Collection $packages): array
    {
        $labels = [
            'recommended' => ['id' => 'Rekomendasi', 'us' => 'Recommended', 'cn' => '推荐'],
            'family' => ['id' => 'Keluarga', 'us' => 'Family', 'cn' => '家庭'],
            'adventure' => ['id' => 'Adventure', 'us' => 'Adventure', 'cn' => '探险'],
            'waterfall' => ['id' => 'Waterfall', 'us' => 'Waterfall', 'cn' => '瀑布'],
            'sunrise' => ['id' => 'Sunrise', 'us' => 'Sunrise', 'cn' => '日出'],
            'culture' => ['id' => 'Budaya', 'us' => 'Culture', 'cn' => '文化'],
            'multi-day' => ['id' => 'Multi-day', 'us' => 'Multi-day', 'cn' => '多日游'],
        ];
        $styles = $packages
            ->flatMap(fn (TourPackage $package) => $package->styles ?? [])
            ->unique()
            ->values();

        return collect(['recommended'])
            ->merge($styles)
            ->unique()
            ->map(fn ($style) => [
                'value' => $style,
                'label' => $labels[$style] ?? [
                    'id' => str($style)->replace('-', ' ')->title()->toString(),
                    'us' => str($style)->replace('-', ' ')->title()->toString(),
                    'cn' => str($style)->replace('-', ' ')->title()->toString(),
                ],
            ])
            ->values()
            ->all();
    }

    private static function availabilityByDate(TourPackage $package): array
    {
        return PackageAvailability::query()
            ->where(function ($query) use ($package) {
                $query->where('tour_package_id', $package->id)
                    ->orWhere('destination_id', $package->destination_id);
            })
            ->orderByRaw('tour_package_id is null')
            ->get()
            ->mapWithKeys(fn (PackageAvailability $availability) => [
                $availability->date->toDateString() => [
                    'status' => $availability->status,
                    'seatsLeft' => $availability->seats_left,
                    'reason' => $availability->reason,
                ],
            ])
            ->all();
    }

    private static function availabilityPayload(array $availability): array
    {
        return [
            'status' => $availability['status'] ?? 'available',
            'seatsLeft' => $availability['seats_left'] ?? $availability['seatsLeft'] ?? null,
            'reason' => $availability['reason'] ?? null,
        ];
    }

    private static function localizedArray(mixed $value): array|string|null
    {
        if (is_string($value) || is_null($value)) {
            return $value;
        }

        if (! is_array($value)) {
            return (string) $value;
        }

        return [
            'id' => $value['id'] ?? $value['us'] ?? $value['cn'] ?? '',
            'us' => $value['us'] ?? $value['id'] ?? $value['cn'] ?? '',
            'cn' => $value['cn'] ?? $value['us'] ?? $value['id'] ?? '',
        ];
    }

    private static function localizedList(mixed $items): array
    {
        if (is_array($items) && (isset($items['id']) || isset($items['us']) || isset($items['cn'])) && (is_array($items['id'] ?? null) || is_array($items['us'] ?? null) || is_array($items['cn'] ?? null))) {
            $count = max(count($items['id'] ?? []), count($items['us'] ?? []), count($items['cn'] ?? []));

            return collect(range(0, max(0, $count - 1)))
                ->map(fn ($index) => [
                    'id' => $items['id'][$index] ?? $items['us'][$index] ?? $items['cn'][$index] ?? '',
                    'us' => $items['us'][$index] ?? $items['id'][$index] ?? $items['cn'][$index] ?? '',
                    'cn' => $items['cn'][$index] ?? $items['us'][$index] ?? $items['id'][$index] ?? '',
                ])
                ->filter(fn ($item) => $item['id'] || $item['us'] || $item['cn'])
                ->values()
                ->all();
        }

        return collect($items ?? [])->map(fn ($item) => self::localizedArray($item))->filter()->values()->all();
    }

    private static function localizedObject(mixed $value): array
    {
        return collect($value ?? [])->mapWithKeys(fn ($item, $key) => [$key => is_array($item) ? self::localizedArray($item) : $item])->all();
    }
}
