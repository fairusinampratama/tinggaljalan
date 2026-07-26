<?php

namespace App\Support;

class ServerPageContent
{
    public static function fromInertiaPage(array $page): ?array
    {
        $component = (string) ($page['component'] ?? '');
        $props = $page['props'] ?? [];
        $language = (string) ($props['language'] ?? data_get($props, 'publicData.language', 'us'));
        $seo = $props['seo'] ?? [];

        $content = match ($component) {
            'HomePage' => self::home($props, $language, $seo),
            'RoutesPage' => self::routesIndex($props, $language, $seo),
            'RouteDetailPage' => self::routeDetail($props, $language, $seo),
            'NewsPage' => self::newsIndex($props, $language, $seo),
            'NewsDetailPage' => self::newsDetail($props, $language, $seo),
            'AboutUsPage' => self::about($props, $language, $seo),
            default => null,
        };

        if (! $content || self::isNoindex($seo)) {
            return null;
        }

        return array_merge([
            'component' => $component,
            'language' => $language,
            'eyebrow' => null,
            'h1' => null,
            'intro' => null,
            'image' => null,
            'sections' => [],
            'links' => [],
        ], $content);
    }

    public static function hreflang(array $page): array
    {
        $canonical = data_get($page, 'props.seo.canonical');

        if (! filled($canonical) || self::isNoindex(data_get($page, 'props.seo', []))) {
            return [];
        }

        return [
            ['hreflang' => 'en', 'href' => self::languageUrl($canonical, 'us')],
            ['hreflang' => 'id', 'href' => self::languageUrl($canonical, 'id')],
            ['hreflang' => 'zh-CN', 'href' => self::languageUrl($canonical, 'cn')],
            ['hreflang' => 'x-default', 'href' => self::languageUrl($canonical, 'us')],
        ];
    }

    private static function home(array $props, string $language, array $seo): array
    {
        $public = $props['publicData'] ?? [];
        $home = $public['home'] ?? [];
        $slides = collect($home['heroSlides'] ?? []);
        $primarySlide = $slides->first();
        $destinations = collect($props['destinations'] ?? [])->take(8)->values();
        $routes = collect($props['featuredRoutes'] ?? [])->take(8)->values();
        $articles = collect($props['latestArticles'] ?? [])->take(4)->values();
        $why = collect($home['whyChooseItems'] ?? [])->take(4)->values();
        $reviews = collect($props['reviews'] ?? $public['reviews'] ?? [])->take(4)->values();
        $faqs = collect($props['faqs'] ?? [])->take(8)->values();

        $destinationText = $destinations
            ->map(fn ($item) => self::localized($item['copy'] ?? null, $language))
            ->filter()
            ->implode(' ');

        return [
            'eyebrow' => self::localized($primarySlide['eyebrow'] ?? null, $language, 'Indonesia private tours'),
            'h1' => data_get($seo, 'title', 'Tinggal Jalan Indonesia Tours'),
            'intro' => self::words([
                data_get($seo, 'description'),
                self::localized($primarySlide['description'] ?? null, $language),
                $destinationText,
                'Tinggal Jalan helps travelers compare Indonesia tour packages before booking. The team focuses on clear itineraries, flexible pickup arrangements, transparent route information, and WhatsApp support for Bromo, Tumpak Sewu, Jogja, Medan, Banyuwangi, East Java, and Sumatra trips. Travelers can browse private routes, check what is included, review pickup notes, read practical travel guides, and request a booking with local support.',
            ]),
            'image' => self::image(
                $primarySlide['desktopImage'] ?? data_get($seo, 'image'),
                self::localized($primarySlide['imageAlt'] ?? null, $language, 'Tinggal Jalan Indonesia tour')
            ),
            'sections' => [
                self::section('Featured Indonesia Destinations', $destinations->map(fn ($item) => [
                    'title' => $item['name'] ?? null,
                    'text' => self::localized($item['copy'] ?? null, $language),
                    'image' => self::image($item['image'] ?? null, ($item['name'] ?? 'Destination').' tour'),
                ])->all()),
                self::section('Popular Private Tour Routes', $routes->map(fn ($item) => [
                    'title' => self::localized($item['title'] ?? null, $language),
                    'text' => self::localized($item['excerpt'] ?? $item['intro'] ?? $item['bestFor'] ?? null, $language),
                    'url' => '/routes/'.($item['slug'] ?? $item['id']),
                    'image' => self::image($item['image'] ?? null, self::localized($item['imageAlt'] ?? $item['title'] ?? null, $language)),
                ])->all()),
                self::section('Why Travelers Choose Tinggal Jalan', $why->map(fn ($item) => [
                    'title' => self::localized($item['title'] ?? null, $language),
                    'text' => self::localized($item['text'] ?? null, $language),
                ])->all()),
                self::section('Latest Indonesia Travel Guides', $articles->map(fn ($item) => [
                    'title' => self::localized($item['title'] ?? null, $language),
                    'text' => self::localized($item['excerpt'] ?? null, $language),
                    'url' => '/news/'.($item['slug'] ?? $item['id']),
                    'image' => self::image($item['coverImage'] ?? null, self::localized($item['coverAlt'] ?? $item['title'] ?? null, $language)),
                ])->all()),
                self::section('Traveler Reviews and Practical Support', $reviews->map(fn ($item) => [
                    'title' => trim(($item['name'] ?? 'Traveler').' '.self::localized($item['origin'] ?? null, $language)),
                    'text' => self::localized($item['text'] ?? null, $language),
                ])->all()),
                self::section('Indonesia Tour Questions', $faqs->map(fn ($item) => [
                    'title' => self::localized($item['question'] ?? null, $language),
                    'text' => self::localized($item['answer'] ?? null, $language),
                ])->all()),
            ],
            'links' => self::coreLinks(),
        ];
    }

    private static function routesIndex(array $props, string $language, array $seo): array
    {
        $routes = collect(data_get($props, 'routes.data', []))->take(12)->values();
        $destinations = collect($props['destinations'] ?? [])->take(10)->values();

        return [
            'eyebrow' => 'Tour packages',
            'h1' => data_get($seo, 'title', 'Indonesia Tour Packages'),
            'intro' => data_get($seo, 'description', 'Compare private Indonesia tour routes with itinerary, pickup, price, and traveler information.'),
            'sections' => [
                self::section('Available Tour Packages', $routes->map(fn ($item) => [
                    'title' => self::localized($item['title'] ?? null, $language),
                    'text' => self::localized($item['excerpt'] ?? $item['intro'] ?? null, $language),
                    'url' => '/routes/'.($item['slug'] ?? $item['id']),
                    'image' => self::image($item['image'] ?? null, self::localized($item['imageAlt'] ?? $item['title'] ?? null, $language)),
                ])->all()),
                self::section('Browse by Destination', $destinations->map(fn ($item) => [
                    'title' => $item['name'] ?? null,
                    'text' => self::localized($item['copy'] ?? null, $language),
                ])->all()),
            ],
            'links' => self::coreLinks(),
        ];
    }

    private static function routeDetail(array $props, string $language, array $seo): ?array
    {
        $route = $props['route'] ?? null;

        if (! is_array($route)) {
            return null;
        }

        $relatedArticles = collect($props['relatedArticles'] ?? [])->take(4)->values();
        $faqs = collect($props['faqs'] ?? [])->take(8)->values();

        return [
            'eyebrow' => self::localized($route['destinationName'] ?? null, $language, 'Indonesia tour route'),
            'h1' => self::localized($route['title'] ?? null, $language, data_get($seo, 'title')),
            'intro' => self::words([
                self::localized($route['intro'] ?? $route['excerpt'] ?? null, $language),
                self::localized($route['bestFor'] ?? null, $language),
                self::localized($route['priceNote'] ?? null, $language),
            ]),
            'image' => self::image($route['image'] ?? null, self::localized($route['imageAlt'] ?? $route['title'] ?? null, $language)),
            'sections' => [
                self::section('Route Highlights', self::simpleItems($route['highlights'] ?? [], $language)),
                self::section('Included in This Trip', self::simpleItems($route['includes'] ?? [], $language)),
                self::section('Itinerary', self::simpleItems($route['itinerary'] ?? [], $language)),
                self::section('Good to Know', self::simpleItems($route['goodToKnow'] ?? $route['notes'] ?? [], $language)),
                self::section('Related Travel Guides', $relatedArticles->map(fn ($item) => [
                    'title' => self::localized($item['title'] ?? null, $language),
                    'text' => self::localized($item['excerpt'] ?? null, $language),
                    'url' => '/news/'.($item['slug'] ?? $item['id']),
                ])->all()),
                self::section('Route Questions', $faqs->map(fn ($item) => [
                    'title' => self::localized($item['question'] ?? null, $language),
                    'text' => self::localized($item['answer'] ?? null, $language),
                ])->all()),
            ],
            'links' => self::coreLinks(),
        ];
    }

    private static function newsIndex(array $props, string $language, array $seo): array
    {
        $articles = collect(data_get($props, 'articles.data', []))->take(12)->values();

        return [
            'eyebrow' => 'Travel guides',
            'h1' => data_get($seo, 'title', 'Travel Guides & News'),
            'intro' => data_get($seo, 'description', 'Read Indonesia travel guides, route updates, and itinerary ideas from Tinggal Jalan.'),
            'sections' => [
                self::section('Latest Travel Articles', $articles->map(fn ($item) => [
                    'title' => self::localized($item['title'] ?? null, $language),
                    'text' => self::localized($item['excerpt'] ?? null, $language),
                    'url' => '/news/'.($item['slug'] ?? $item['id']),
                    'image' => self::image($item['coverImage'] ?? null, self::localized($item['coverAlt'] ?? $item['title'] ?? null, $language)),
                ])->all()),
            ],
            'links' => self::coreLinks(),
        ];
    }

    private static function newsDetail(array $props, string $language, array $seo): ?array
    {
        $article = $props['article'] ?? null;

        if (! is_array($article)) {
            return null;
        }

        $sections = collect($article['sections'] ?? [])->map(fn ($item) => [
            'title' => self::localized($item['heading'] ?? null, $language),
            'text' => self::localized($item['body'] ?? null, $language),
        ])->all();
        $relatedRoutes = collect($props['relatedRoutes'] ?? [])->take(4)->map(fn ($item) => [
            'title' => self::localized($item['title'] ?? null, $language),
            'text' => self::localized($item['bestFor'] ?? $item['excerpt'] ?? null, $language),
            'url' => '/routes/'.($item['slug'] ?? $item['id']),
        ])->all();
        $relatedArticles = collect($props['relatedArticles'] ?? [])->take(4)->map(fn ($item) => [
            'title' => self::localized($item['title'] ?? null, $language),
            'text' => self::localized($item['excerpt'] ?? null, $language),
            'url' => '/news/'.($item['slug'] ?? $item['id']),
        ])->all();

        return [
            'eyebrow' => self::localized($article['destinationName'] ?? null, $language, 'Indonesia travel guide'),
            'h1' => self::localized($article['title'] ?? null, $language, data_get($seo, 'title')),
            'intro' => self::localized($article['excerpt'] ?? null, $language, data_get($seo, 'description')),
            'image' => self::image($article['coverImage'] ?? null, self::localized($article['coverAlt'] ?? $article['title'] ?? null, $language)),
            'sections' => [
                self::section('Article Guide', $sections),
                self::section('Related Tour Routes', $relatedRoutes),
                self::section('More Travel Guides', $relatedArticles),
            ],
            'links' => self::coreLinks(),
        ];
    }

    private static function about(array $props, string $language, array $seo): array
    {
        $page = $props['aboutPage'] ?? [];
        $hero = $page['hero'] ?? [];
        $story = $page['story'] ?? [];
        $values = $page['values'] ?? [];
        $profile = $page['profile'] ?? [];
        $team = collect($props['teamMembers'] ?? [])->take(6)->values();
        $milestones = collect($props['milestones'] ?? [])->take(6)->values();

        return [
            'eyebrow' => self::localized($hero['eyebrow'] ?? null, $language, 'About Tinggal Jalan'),
            'h1' => self::localized($hero['title'] ?? null, $language, data_get($seo, 'title', 'About Tinggal Jalan')),
            'intro' => self::words([
                self::localized($hero['intro'] ?? null, $language),
                self::localized($story['body'] ?? $story['intro'] ?? null, $language),
                self::localized($profile['operating_description'] ?? null, $language),
            ]),
            'image' => self::image($hero['image'] ?? data_get($seo, 'image'), self::localized($hero['image_alt'] ?? null, $language, 'Tinggal Jalan team')),
            'sections' => [
                self::section(self::localized($story['title'] ?? null, $language, 'Our Story'), [[
                    'text' => self::localized($story['body'] ?? null, $language),
                    'image' => self::image($story['image'] ?? null, self::localized($story['image_alt'] ?? null, $language, 'Tinggal Jalan story')),
                ]]),
                self::section(self::localized($values['title'] ?? null, $language, 'Our Travel Values'), collect($values['items'] ?? [])->map(fn ($item) => [
                    'title' => self::localized($item['title'] ?? null, $language),
                    'text' => self::localized($item['text'] ?? null, $language),
                ])->all()),
                self::section('Tinggal Jalan Team', $team->map(fn ($member) => [
                    'title' => $member['name'] ?? null,
                    'text' => self::words([self::localized($member['role'] ?? null, $language), self::localized($member['biography'] ?? null, $language)]),
                    'image' => self::image($member['portrait'] ?? null, self::localized($member['portraitAlt'] ?? null, $language, ($member['name'] ?? 'Team member').' portrait')),
                ])->all()),
                self::section('Company Milestones', $milestones->map(fn ($item) => [
                    'title' => self::localized($item['title'] ?? null, $language),
                    'text' => self::localized($item['description'] ?? null, $language),
                    'image' => self::image($item['image'] ?? null, self::localized($item['imageAlt'] ?? null, $language)),
                ])->all()),
            ],
            'links' => self::coreLinks(),
        ];
    }

    private static function section(string $title, array $items): array
    {
        return [
            'title' => $title,
            'items' => collect($items)
                ->filter(fn ($item): bool => filled($item['title'] ?? null) || filled($item['text'] ?? null))
                ->values()
                ->all(),
        ];
    }

    private static function simpleItems(array $items, string $language): array
    {
        return collect($items)->map(fn ($item) => ['text' => self::localized($item, $language)])->all();
    }

    private static function image(?string $src, ?string $alt): ?array
    {
        if (! filled($src)) {
            return null;
        }

        return [
            'src' => PublicSite::assetPath($src),
            'alt' => filled($alt) ? trim(strip_tags((string) $alt)) : 'Tinggal Jalan Indonesia tour',
        ];
    }

    private static function localized(mixed $value, string $language, ?string $fallback = null): string
    {
        return trim(strip_tags(PublicSite::localized($value, $language, $fallback)));
    }

    private static function words(array $parts): string
    {
        return trim(collect($parts)->filter(fn ($part) => filled($part))->implode(' '));
    }

    private static function isNoindex(array $seo): bool
    {
        return str_contains((string) data_get($seo, 'robots', ''), 'noindex');
    }

    private static function coreLinks(): array
    {
        return [
            ['label' => 'Indonesia tour packages', 'url' => '/routes'],
            ['label' => 'Travel guides and news', 'url' => '/news'],
            ['label' => 'About Tinggal Jalan', 'url' => '/about-us'],
            ['label' => 'Bromo tour packages', 'url' => '/routes/BROMO'],
            ['label' => 'Chat with Tinggal Jalan on WhatsApp', 'url' => 'https://wa.me/62811388330'],
        ];
    }

    private static function languageUrl(string $canonical, string $language): string
    {
        $parts = parse_url($canonical);
        $query = [];

        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        if ($language === 'us') {
            unset($query['lang']);
        } else {
            $query['lang'] = $language;
        }

        $path = $parts['path'] ?? '/';
        $authority = $parts['host'] ?? parse_url(Seo::baseUrl(), PHP_URL_HOST);
        $authority .= isset($parts['port']) ? ':'.$parts['port'] : '';
        $url = ($parts['scheme'] ?? 'https').'://'.$authority.$path;
        $queryString = http_build_query($query);

        return $queryString ? $url.'?'.$queryString : $url;
    }
}
