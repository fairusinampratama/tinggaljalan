<?php

namespace App\Support;

use App\Models\AboutPage;
use App\Models\NewsArticle;
use App\Models\TourPackage;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Seo
{
    public const SITE_NAME = 'Tinggal Jalan';

    public const DEFAULT_DESCRIPTION = 'Plan private Indonesia tours with Tinggal Jalan. Compare Bromo, Tumpak Sewu, Jogja, and Medan routes with clear itineraries, flexible pickup, and WhatsApp support.';

    public const DEFAULT_IMAGE = 'images/hero-bromo.jpg';

    public static function baseUrl(): string
    {
        return rtrim(config('app.url') ?: 'https://tinggaljalan.com', '/');
    }

    public static function canonical(string $path = '/'): string
    {
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return self::baseUrl().'/'.ltrim($path, '/');
    }

    public static function assetUrl(?string $path = null): string
    {
        $path = PublicSite::assetPath($path ?: self::DEFAULT_IMAGE);

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return self::canonical($path);
    }

    public static function page(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Tinggal Jalan | Indonesia Tours & Private Trips',
            'description' => self::DEFAULT_DESCRIPTION,
            'canonical' => self::canonical('/'),
            'robots' => 'index,follow',
            'og_type' => 'website',
            'image' => self::assetUrl(),
            'twitter_card' => 'summary_large_image',
            'published_time' => null,
            'modified_time' => null,
            'json_ld' => [],
        ], $overrides);
    }

    public static function noindex(array $overrides = []): array
    {
        return self::page(array_merge(['robots' => 'noindex,nofollow'], $overrides));
    }

    public static function home(): array
    {
        return self::page([
            'canonical' => self::canonical('/'),
            'json_ld' => [
                self::websiteJsonLd(),
                self::organizationJsonLd(),
            ],
        ]);
    }

    public static function about(AboutPage $page, string $language): array
    {
        $seo = $page->seo ?? [];
        $profile = $page->profile_section ?? [];
        $organization = self::organizationJsonLd();

        if (($profile['show_legal_name'] ?? false) && filled($profile['legal_name'] ?? null)) {
            $organization['legalName'] = $profile['legal_name'];
        }
        if (($profile['show_founding_year'] ?? false) && filled($profile['founding_year'] ?? null)) {
            $organization['foundingDate'] = (string) $profile['founding_year'];
        }
        if (($profile['show_registration'] ?? false) && filled($profile['registration'] ?? null)) {
            $organization['identifier'] = $profile['registration'];
        }

        $title = PublicSite::localized($seo['title'] ?? [], $language, 'About TinggalJalan');
        $description = PublicSite::localized($seo['description'] ?? [], $language, self::DEFAULT_DESCRIPTION);
        $image = $seo['image'] ?? ($page->hero['image'] ?? null);

        return self::page([
            'title' => $title,
            'description' => $description,
            'canonical' => self::canonical('/about-us'),
            'image' => self::assetUrl($image),
            'json_ld' => [[
                '@context' => 'https://schema.org',
                '@type' => 'AboutPage',
                'name' => $title,
                'description' => $description,
                'url' => self::canonical('/about-us'),
                'mainEntity' => $organization,
            ]],
        ]);
    }

    public static function routesIndex(Collection $packages, bool $hasSearch): array
    {
        return self::page([
            'title' => 'Indonesia Tour Packages | Tinggal Jalan',
            'description' => 'Compare private Indonesia tour packages for Bromo, Tumpak Sewu, Jogja, and Medan with clear itineraries, pickup options, prices, and traveler reviews.',
            'canonical' => self::canonical('/routes'),
            'robots' => $hasSearch ? 'noindex,follow' : 'index,follow',
            'json_ld' => [
                self::collectionJsonLd('Indonesia Tour Packages', '/routes', $packages->map(fn (TourPackage $package) => [
                    '@type' => 'ListItem',
                    'position' => $packages->search($package) + 1,
                    'url' => self::canonical('/routes/'.trim((string) $package->slug)),
                    'name' => PublicSite::localized($package->title, 'us'),
                ])->values()->all()),
            ],
        ]);
    }

    public static function routeDetail(TourPackage $package, string $language): array
    {
        $title = PublicSite::localized($package->title, $language);
        $description = PublicSite::localized($package->excerpt, $language);
        $description = filled($description) ? $description : PublicSite::localized($package->intro, $language);

        return self::page([
            'title' => "{$title} | Tinggal Jalan",
            'description' => $description,
            'canonical' => self::canonical('/routes/'.trim((string) $package->slug)),
            'og_type' => 'product',
            'image' => self::assetUrl($package->cover_image),
            'json_ld' => [
                self::productJsonLd($package, $language),
                self::touristTripJsonLd($package, $language),
            ],
        ]);
    }

    public static function newsIndex(Collection $articles, bool $hasSearch, string $language = 'us'): array
    {
        $title = match ($language) {
            'id' => 'Berita & Panduan Wisata | Tinggal Jalan',
            'cn' => '旅游攻略与动态 | Tinggal Jalan',
            default => 'Travel Guides & News | Tinggal Jalan',
        };
        $description = match ($language) {
            'id' => 'Baca berita, tips perjalanan, itinerary, dan panduan destinasi untuk Bromo, Jogja, Tumpak Sewu, Medan, dan private trip Indonesia.',
            'cn' => '阅读 Tinggal Jalan 的印尼私人旅行攻略、路线更新、行程建议和目的地指南。',
            default => 'Travel guides, itinerary ideas, and route updates from Tinggal Jalan for Indonesia private trips.',
        };

        return self::page([
            'title' => $title,
            'description' => $description,
            'canonical' => self::canonical('/news'),
            'robots' => $hasSearch ? 'noindex,follow' : 'index,follow',
            'json_ld' => [
                self::collectionJsonLd($title, '/news', $articles->map(fn (NewsArticle $article) => [
                    '@type' => 'ListItem',
                    'position' => $articles->search($article) + 1,
                    'url' => self::canonical('/news/'.trim((string) $article->slug)),
                    'name' => PublicSite::localized($article->title, $language),
                ])->values()->all(), 'Blog'),
            ],
        ]);
    }

    public static function articleDetail(NewsArticle $article, string $language): array
    {
        $title = PublicSite::localized($article->seo['title'] ?? $article->title, $language);
        $description = PublicSite::localized($article->seo['description'] ?? $article->excerpt, $language);
        $modified = $article->content_updated_at ?? $article->updated_at;

        return self::page([
            'title' => "{$title} | Tinggal Jalan",
            'description' => $description,
            'canonical' => self::canonical('/news/'.trim((string) $article->slug)),
            'og_type' => 'article',
            'image' => self::assetUrl($article->cover_image),
            'published_time' => optional($article->published_at)->toIso8601String(),
            'modified_time' => optional($modified)->toIso8601String(),
            'json_ld' => [
                self::articleJsonLd($article, $language),
            ],
        ]);
    }

    public static function websiteJsonLd(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => self::SITE_NAME,
            'url' => self::canonical('/'),
            'inLanguage' => ['en', 'id', 'zh-CN'],
        ];
    }

    public static function organizationJsonLd(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'TravelAgency',
            'name' => self::SITE_NAME,
            'url' => self::canonical('/'),
            'logo' => self::assetUrl('favicon.png'),
            'image' => self::assetUrl(),
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'url' => PublicSite::whatsappBase(),
                'availableLanguage' => ['Indonesian', 'English', 'Chinese'],
            ],
            'areaServed' => 'Indonesia',
        ];
    }

    public static function collectionJsonLd(string $name, string $path, array $items, string $type = 'CollectionPage'): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => $type,
            'name' => $name,
            'url' => self::canonical($path),
            'mainEntity' => [
                '@type' => 'ItemList',
                'itemListElement' => $items,
            ],
        ];
    }

    public static function productJsonLd(TourPackage $package, string $language): array
    {
        $currency = $language === 'id' ? 'IDR' : 'USD';
        $price = app(TierPricingResolver::class)->startingPrice($package, $currency);

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => PublicSite::localized($package->title, $language),
            'description' => PublicSite::localized($package->excerpt, $language),
            'image' => self::assetUrl($package->cover_image),
            'brand' => [
                '@type' => 'Brand',
                'name' => self::SITE_NAME,
            ],
            'category' => 'Tour',
            'areaServed' => $package->destination?->name,
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => $currency,
                'price' => $price,
                'availability' => 'https://schema.org/InStock',
                'url' => self::canonical('/routes/'.trim((string) $package->slug)),
            ],
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => (float) ($package->rating ?? 5),
                'reviewCount' => max(1, (int) ($package->review_count ?? 1)),
                'bestRating' => 5,
                'worstRating' => 1,
            ],
        ];
    }

    public static function touristTripJsonLd(TourPackage $package, string $language): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'TouristTrip',
            'name' => PublicSite::localized($package->title, $language),
            'description' => PublicSite::localized($package->intro ?? $package->excerpt, $language),
            'image' => self::assetUrl($package->cover_image),
            'url' => self::canonical('/routes/'.trim((string) $package->slug)),
            'touristType' => PublicSite::localized($package->best_for, $language),
            'itinerary' => $package->itineraryItems->map(fn ($item) => [
                '@type' => 'ItemList',
                'name' => PublicSite::localized($item->title, $language),
                'description' => PublicSite::localized($item->description, $language),
            ])->values()->all(),
        ];
    }

    public static function articleJsonLd(NewsArticle $article, string $language): array
    {
        $type = in_array($article->articleCategory?->slug, ['kabar', 'news', 'updates', 'route-update'], true)
            ? 'NewsArticle'
            : 'BlogPosting';
        $modified = $article->content_updated_at ?? $article->updated_at;

        return [
            '@context' => 'https://schema.org',
            '@type' => $type,
            'headline' => PublicSite::localized($article->title, $language),
            'description' => PublicSite::localized($article->excerpt, $language),
            'image' => self::assetUrl($article->cover_image),
            'datePublished' => optional($article->published_at)->toIso8601String(),
            'dateModified' => optional($modified)->toIso8601String(),
            'author' => [
                '@type' => 'Organization',
                'name' => self::SITE_NAME,
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => self::SITE_NAME,
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => self::assetUrl('favicon.png'),
                ],
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => self::canonical('/news/'.trim((string) $article->slug)),
            ],
        ];
    }
}
