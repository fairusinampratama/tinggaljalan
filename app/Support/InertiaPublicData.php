<?php

namespace App\Support;

use App\Models\ArticleCategory;
use App\Models\Destination;
use App\Models\Faq;
use App\Models\HeroSlide;
use App\Models\NewsArticle;
use App\Models\PackageAddOn;
use App\Models\PackageAvailability;
use App\Models\PlatformLink;
use App\Models\Review;
use App\Models\SiteSetting;
use App\Models\TourPackage;
use App\Models\TrustStat;
use App\Models\WhyChooseItem;
use App\Payments\PaymentSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class InertiaPublicData
{
    public static function shared(Request $request): array
    {
        $language = PublicSite::language($request);

        return [
            'language' => $language,
            'site' => self::site(),
            'home' => self::home(),
            'bookingOptions' => self::bookingOptions($request),
            'paymentSettings' => app(PaymentSettingsService::class)->publicPayload(),
            'destinations' => Destination::query()->active()->ordered()->get()->map(fn (Destination $destination) => self::destination($destination))->values(),
            'categories' => ArticleCategory::query()->active()->ordered()->get()->map(fn (ArticleCategory $category) => [
                'value' => $category->slug,
                'label' => self::localizedArray($category->label),
            ])->values(),
            'routeStyles' => RouteFilterOptions::publicOptions(),
            'reviews' => Review::query()->active()->featured()->ordered()->limit(Review::MAX_ACTIVE_FEATURED)->get()->map(fn (Review $review) => [
                'name' => $review->name,
                'origin' => self::localizedArray($review->origin),
                'rating' => (float) $review->rating,
                'text' => self::localizedArray($review->text),
                'source' => self::localizedArray($review->source),
            ])->values(),
            'trustStats' => TrustStat::query()->active()->ordered()->limit(TrustStat::MAX_ACTIVE)->get()->map(fn (TrustStat $stat) => [
                'title' => self::localizedArray($stat->title),
                'value' => self::localizedArray($stat->value),
                'icon' => $stat->icon_key,
            ])->values(),
            'platformLinks' => PlatformLink::query()->active()->ordered()->limit(PlatformLink::MAX_ACTIVE)->get()->map(fn (PlatformLink $platform) => [
                'name' => $platform->name,
                'url' => $platform->url,
                'logo' => self::assetPath($platform->logo),
                'alt' => $platform->alt ?: $platform->name.' logo',
            ])->values(),
            'whatsappUrl' => PublicSite::whatsappUrl(['Halo Tinggal Jalan, saya ingin konsultasi rute perjalanan.']),
        ];
    }

    public static function site(): array
    {
        $site = SiteSetting::first();

        $contact = [
            'email' => $site?->contact_email,
            'address' => $site?->business_address,
            'map_url' => $site?->google_maps_url,
            'hours' => $site?->service_hours ?? [],
            'whatsapp' => $site?->whatsapp_number,
        ];

        // Generate tel and mailto links automatically
        if (! empty($site->contact_email)) {
            $contact['email_url'] = 'mailto:'.$site->contact_email;
        }
        if (! empty($site->whatsapp_number)) {
            $number = preg_replace('/\D+/', '', $site->whatsapp_number);
            $contact['whatsapp_url'] = 'tel:+'.$number;
        }

        return [
            'logoUrl' => self::assetPath($site?->logo_url ?? '/images/logo-tj.png'),
            'trustBadges' => $site?->trust_badges ?? [],
            'contactDetails' => $contact,
            'whatsappNumber' => preg_replace('/\D+/', '', $site?->whatsapp_number ?? '6281234567890'),
            'whatsappBaseUrl' => PublicSite::whatsappBase(),
        ];
    }

    public static function home(): array
    {
        $site = SiteSetting::query()->first();

        return [
            'heroSettings' => [
                'autoplayEnabled' => (bool) ($site?->hero_autoplay_enabled ?? true),
                'autoplayInterval' => min(15000, max(5000, (int) ($site?->hero_autoplay_interval ?? 8000))),
            ],
            'heroSlides' => HeroSlide::query()->activeScheduled()->ordered()->limit(5)->get()->map(fn ($slide) => [
                'id' => $slide->id,
                'desktopImage' => self::assetPath($slide->desktop_image),
                'mobileImage' => self::assetPath($slide->mobile_image ?? $slide->desktop_image),
                'imageAlt' => self::localizedArray($slide->image_alt),
                'eyebrow' => self::localizedArray($slide->eyebrow),
                'heading' => self::localizedArray($slide->heading),
                'description' => self::localizedArray($slide->description),
                'primaryCtaLabel' => self::localizedArray($slide->primary_cta_label),
                'primaryCtaUrl' => $slide->primary_cta_url,
                'secondaryCtaLabel' => self::localizedArray($slide->secondary_cta_label),
                'secondaryCtaUrl' => $slide->secondary_cta_url,
                'textAlignment' => $slide->text_alignment,
                'focalPosition' => $slide->focal_position,
                'overlayStrength' => $slide->overlay_strength,
            ])->values()->all(),
            'whyChooseItems' => WhyChooseItem::query()->active()->ordered()->limit(3)->get()->map(fn ($item) => [
                'title' => self::localizedArray($item->title ?? []),
                'text' => self::localizedArray($item->text ?? []),
                'icon' => $item->icon ?? 'compass',
            ])->values()->all(),
        ];
    }

    public static function bookingOptions(Request $request): array
    {
        return [
            'destinationOptions' => Destination::query()->active()->ordered()->pluck('name')->values()->all(),
            'paxMin' => (int) config('booking.minimum_guests'),
            'paxMax' => (int) config('booking.maximum_guests'),
            'largeGroupThreshold' => (int) config('booking.large_group_threshold'),
            'travelerTypeOptions' => self::settingListConfig('booking.traveler_type_options'),
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
            'image' => self::assetPath($destination->cover_image),
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

        $pricingResolver = app(TierPricingResolver::class);
        $priceNote = self::localizedArray($package->price_note ?: [
            'id' => 'Harga masih estimasi sampai jadwal dan kebutuhan grup dikonfirmasi.',
            'us' => 'Estimated price until schedule and group needs are confirmed.',
            'cn' => '价格为预估，需确认日期和团队需求。',
        ]);

        return [
            'id' => trim((string) $package->slug),
            'slug' => trim((string) $package->slug),
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
            'basePrice' => $pricingResolver->startingPrice($package, 'IDR'),
            'basePriceIdr' => $pricingResolver->startingPrice($package, 'IDR'),
            'basePriceUsd' => $pricingResolver->startingPrice($package, 'USD'),
            'pricing' => [
                'mode' => $package->pricing_mode ?? 'flat',
                'tiers' => $package->priceTiers()->get()->map(fn ($tier) => [
                    'id' => $tier->id,
                    'minPax' => $tier->min_pax,
                    'maxPax' => $tier->max_pax,
                    'priceIdr' => $tier->price_idr,
                    'priceUsd' => $tier->price_usd,
                ])->values()->all(),
            ],
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
            'addOns' => $package->packageAddOns
                ->filter(fn (PackageAddOn $packageAddOn): bool => $packageAddOn->is_active)
                ->map(fn (PackageAddOn $packageAddOn) => self::packageAddOn($packageAddOn))
                ->values()
                ->all(),
            'packageOptions' => [[
                'id' => trim((string) $package->slug),
                'title' => self::localizedArray($package->title),
                'description' => self::localizedArray($package->intro ?? $package->excerpt),
                'basePriceIdr' => $pricingResolver->startingPrice($package, 'IDR'),
                'basePriceUsd' => $pricingResolver->startingPrice($package, 'USD'),
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
            'availabilityRules' => self::availabilityRules($package),
        ];
    }

    public static function routes(Collection $packages): array
    {
        return $packages->map(fn (TourPackage $package) => self::route($package))->values()->all();
    }

    public static function routeCard(TourPackage $package): array
    {
        return array_intersect_key(self::route($package), array_flip(['id', 'slug', 'destinationId', 'destinationName', 'title', 'category', 'tag', 'badge', 'excerpt', 'intro', 'why', 'bestFor', 'duration', 'difficulty', 'basePrice', 'basePriceIdr', 'basePriceUsd', 'image', 'imageAlt', 'pickupLabel', 'groupType', 'rating', 'reviewCount', 'reviewSource', 'styles', 'featured', 'relatedArticleSlugs']));
    }

    public static function routeCards(Collection $packages): array
    {
        return $packages->map(fn (TourPackage $package) => self::routeCard($package))->values()->all();
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
            'tags' => self::articleTags($article->tags),
            'seo' => self::localizedObject($article->seo),
            'sections' => self::articleSections($article->sections),
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

    public static function articleCard(NewsArticle $article): array
    {
        return array_intersect_key(self::article($article), array_flip(['id', 'slug', 'title', 'excerpt', 'category', 'destinationId', 'destinationName', 'publishedDate', 'updatedDate', 'readingTime', 'coverImage', 'coverAlt', 'tags', 'relatedRouteIds', 'isFeatured', 'publishedTime', 'modifiedTime']));
    }

    public static function articleCards(Collection $articles): array
    {
        return $articles->map(fn (NewsArticle $article) => self::articleCard($article))->values()->all();
    }

    public static function packageAddOn(PackageAddOn $packageAddOn): array
    {
        return [
            'id' => (string) $packageAddOn->id,
            'slug' => (string) $packageAddOn->id,
            'title' => self::localizedArray($packageAddOn->title),
            'description' => self::localizedArray($packageAddOn->description),
            'priceIdr' => $packageAddOn->price_idr,
            'priceUsd' => $packageAddOn->price_usd,
            'pricing' => $packageAddOn->pricing_type === 'per_pax' ? 'perPax' : 'perBooking',
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
                'currency' => PublicSite::bookingCurrency($draft['traveler_type'] ?? $draft['travelerType'] ?? null),
                'addOns' => $draft['add_ons'] ?? $draft['addOns'] ?? [],
                'name' => $draft['name'] ?? '',
                'email' => $draft['email'] ?? '',
                'whatsapp' => $draft['whatsapp'] ?? '',
                'whatsappCountry' => $draft['whatsapp_country'] ?? $draft['whatsappCountry'] ?? 'ID',
                'voucher' => $draft['voucher'] ?? '',
                'notes' => $draft['notes'] ?? '',
            ],
            'summary' => [
                'currency' => $summary['currency'],
                'pax' => $summary['pax'],
                'base' => $summary['base'],
                'basePrice' => $summary['base'],
                'pricingStatus' => $summary['pricing_status'],
                'pricingMode' => $summary['pricing_mode'],
                'unitPrice' => $summary['unit_price'],
                'packageSubtotal' => $summary['package_subtotal'],
                'selectedTier' => $summary['selected_tier'],
                'savingsPerPerson' => $summary['savings_per_person'],
                'quoteRequired' => $summary['quote_required'],
                'subtotal' => $summary['subtotal'],
                'discount' => $summary['discount'],
                'total' => $summary['total'],
                'paymentGateway' => $summary['payment_gateway'],
                'paymentNote' => $summary['payment_note'],
                'usdPaymentNote' => $summary['usd_payment_note'],
                'voucher' => $summary['voucher'] ? [
                    'code' => $summary['voucher']->code,
                    'label' => $summary['voucher']->label,
                ] : null,
                'addOns' => $summary['addOns']->map(function (PackageAddOn $packageAddOn) use ($summary) {
                    $addOnPayload = self::packageAddOn($packageAddOn);
                    $unitPrice = $summary['currency'] === 'USD' ? (int) $packageAddOn->price_usd : (int) $packageAddOn->price_idr;
                    $quantity = $packageAddOn->pricing_type === 'per_pax' ? $summary['pax'] : 1;

                    return $addOnPayload + [
                        'unitPrice' => $unitPrice,
                        'quantity' => $quantity,
                        'total' => $unitPrice * $quantity,
                    ];
                })->values()->all(),
                'addOnsTotal' => $summary['addOns']->sum(function (PackageAddOn $packageAddOn) use ($summary) {
                    $unitPrice = $summary['currency'] === 'USD' ? (int) $packageAddOn->price_usd : (int) $packageAddOn->price_idr;

                    return $packageAddOn->pricing_type === 'per_pax' ? $unitPrice * $summary['pax'] : $unitPrice;
                }),
            ],
            'availability' => self::availabilityPayload(PublicSite::availability($package, $draft['date'] ?? null), $summary['pax']),
        ];
    }

    private static function assetPath(?string $path): string
    {
        return PublicSite::assetPath($path);
    }

    private static function settingListConfig(string $configKey): array
    {
        return collect(config($configKey, []))->map(function ($item) {
            if (! is_array($item)) {
                return $item;
            }

            return collect($item)->mapWithKeys(function ($value, $field) {
                $localizedFields = ['label', 'meta', 'title', 'text', 'description'];

                return [$field => in_array($field, $localizedFields, true) ? self::localizedArray($value) : $value];
            })->all();
        })->values()->all();
    }

    private static function availabilityByDate(TourPackage $package): array
    {
        return app(PackageAvailabilityResolver::class)
            ->rulesByDate($package)
            ->mapWithKeys(fn (PackageAvailability $availability) => [
                $availability->date->toDateString() => [
                    'status' => $availability->status,
                    'seatsLeft' => $availability->seats_left,
                    'reason' => $availability->reason,
                ],
            ])
            ->all();
    }

    private static function availabilityRules(TourPackage $package): array
    {
        return app(PackageAvailabilityResolver::class)
            ->rules($package)
            ->map(fn (PackageAvailability $availability) => [
                'scope' => $availability->tour_package_id ? 'package' : 'destination',
                'startDate' => $availability->date->toDateString(),
                'endDate' => $availability->end_date?->toDateString(),
                'openEnded' => (bool) $availability->is_open_ended,
                'status' => $availability->status,
                'seatsLeft' => $availability->seats_left,
                'reason' => $availability->reason,
            ])
            ->values()
            ->all();
    }

    private static function availabilityPayload(array $availability, ?int $pax = null): array
    {
        return [
            'status' => $availability['status'] ?? 'available',
            'seatsLeft' => $availability['seats_left'] ?? $availability['seatsLeft'] ?? null,
            'reason' => $availability['reason'] ?? null,
            'capacityExceeded' => ($availability['status'] ?? null) === 'limited'
                && filled($availability['seats_left'] ?? $availability['seatsLeft'] ?? null)
                && filled($pax)
                && $pax > (int) ($availability['seats_left'] ?? $availability['seatsLeft']),
        ];
    }

    private static function articleTags(mixed $tags): array
    {
        if (! is_array($tags)) {
            return [];
        }

        return collect($tags)
            ->map(function ($tag) {
                if (is_array($tag)) {
                    return self::localizedArray($tag);
                }

                $value = trim((string) $tag);

                return [
                    'id' => $value,
                    'us' => $value,
                    'cn' => $value,
                ];
            })
            ->filter(fn ($tag): bool => filled($tag['us'] ?? null) || filled($tag['id'] ?? null) || filled($tag['cn'] ?? null))
            ->values()
            ->all();
    }

    private static function articleSections(mixed $sections): array
    {
        if (! is_array($sections)) {
            return [];
        }

        return collect($sections)
            ->map(function ($section, int $index): ?array {
                if (! is_array($section)) {
                    return null;
                }

                $heading = self::localizedPlainArray($section['heading'] ?? $section['title'] ?? []);
                $body = self::localizedPlainArray($section['body'] ?? $section['content'] ?? $section['text'] ?? []);

                if (! filled($heading['us'] ?? null) && filled($body['us'] ?? null)) {
                    $heading = self::localizedPlainArray(['us' => 'Section '.($index + 1)]);
                }

                return [
                    'heading' => $heading,
                    'body' => $body,
                ];
            })
            ->filter(fn (?array $section): bool => is_array($section)
                && (filled($section['heading']['us'] ?? null) || filled($section['body']['us'] ?? null)))
            ->values()
            ->all();
    }

    private static function localizedPlainArray(mixed $value): array
    {
        $localized = self::localizedArray($value);

        if (! is_array($localized)) {
            $localized = self::localizedArray(['us' => $localized]);
        }

        return collect($localized)
            ->map(fn ($item): string => trim(strip_tags((string) $item)))
            ->all();
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
            'id' => self::firstFilled($value['id'] ?? null, $value['us'] ?? null, $value['cn'] ?? null),
            'us' => self::firstFilled($value['us'] ?? null, $value['id'] ?? null, $value['cn'] ?? null),
            'cn' => self::firstFilled($value['cn'] ?? null, $value['us'] ?? null, $value['id'] ?? null),
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

    private static function firstFilled(mixed ...$values): string
    {
        foreach ($values as $value) {
            if (filled($value)) {
                return (string) $value;
            }
        }

        return '';
    }
}
