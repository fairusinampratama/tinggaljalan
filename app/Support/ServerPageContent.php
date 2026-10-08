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
            'PrivacyPolicyPage' => self::privacy($language),
            default => null,
        };

        if (! $content) {
            return null;
        }

        $t = self::copy($language);
        $site = $props['publicData']['site'] ?? [];
        $contact = $site['contactDetails'] ?? [];
        $content['sections'][] = ['title' => $t['contactTitleFooter'], 'text' => $t['contactTextFooter'], 'items' => [
            ['text' => $t['footerText']],
            ['text' => $contact['address'] ?? '', 'title' => $t['footerOfficeAddress'], 'url' => $contact['map_url'] ?? ''],
            ['text' => implode(' · ', $site['trustBadges'] ?? [])],
        ]];
        $content['links'] = collect(array_merge(self::coreLinks(), $content['links'] ?? []))->unique('url')->values()->all();
        if (empty($site['aboutEnabled'])) {
            $content['links'] = array_values(array_filter($content['links'], fn ($link) => $link['url'] !== '/about-us'));
        }
        if (! empty($contact['email'])) {
            $content['links'][] = ['label' => $contact['email'], 'url' => 'mailto:'.$contact['email']];
        }
        if (! empty($props['publicData']['whatsappUrl'])) {
            $content['links'][] = ['label' => $t['sendToWhatsapp'], 'url' => $props['publicData']['whatsappUrl']];
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
            ['hreflang' => 'en', 'href' => $canonical],
            ['hreflang' => 'x-default', 'href' => $canonical],
        ];
    }

    private static function copy(string $language): array
    {
        // The browser and Blade read the same translation source.
        $language = in_array($language, PublicSite::LANGUAGES, true) ? $language : 'us';

        static $copies = [];

        return $copies[$language] ??= json_decode(file_get_contents(resource_path("js/data/translations/{$language}.json")), true, flags: JSON_THROW_ON_ERROR);
    }

    private static function cards(array $items, string $kind, string $language): array
    {
        return collect($items)->map(fn ($item) => [
            'title' => self::localized($item['title'] ?? null, $language),
            'text' => self::localized($kind === 'news' ? ($item['excerpt'] ?? null) : ($item['intro'] ?? null), $language),
            'url' => '/'.$kind.'/'.($item['slug'] ?? $item['id']),
            'image' => self::image($item[$kind === 'news' ? 'coverImage' : 'image'] ?? null, self::localized($item[$kind === 'news' ? 'coverAlt' : 'imageAlt'] ?? $item['title'] ?? null, $language)),
            'headingLevel' => 3,
            'meta' => $kind === 'routes' ? self::words([
                self::localized($item['duration'] ?? null, $language),
                self::localized($item['pickupLabel'] ?? null, $language),
                self::localized($item['groupType'] ?? null, $language),
                self::price($item, $language).' '.self::copy($language)['perPerson'],
                self::copy($language)['priceVariesByGroupSize'],
            ]) : self::words([self::localized($item['readingTime'] ?? null, $language), implode(' · ', array_column(self::simpleItems(array_slice($item['tags'] ?? [], 0, 2), $language), 'text'))]),
        ])->all();
    }

    private static function home(array $props, string $language, array $seo): array
    {
        $t = self::copy($language);
        $public = $props['publicData'] ?? [];
        $home = $public['home'] ?? [];
        $slides = $home['heroSlides'] ?? [];
        if (! $slides) {
            $slides = [['heading' => $t['heroTitle'], 'description' => $t['heroText']]];
        }
        $sections = collect($slides)->map(fn ($slide) => [
            'title' => self::localized($slide['heading'] ?? null, $language),
            'text' => self::localized($slide['description'] ?? null, $language),
            'image' => self::image($slide['desktopImage'] ?? null, self::localized($slide['imageAlt'] ?? null, $language)),
            'links' => self::actionLinks($slide, $language, ['primaryCta', 'secondaryCta']),
            'items' => [],
        ])->all();
        $sections[] = self::section($t['promotionsTitle'], collect($props['promotions'] ?? [])->map(fn ($item) => [
            'title' => self::localized($item['title'] ?? null, $language),
            'text' => self::localized($item['description'] ?? null, $language),
            'meta' => self::words([
                ($item['discountType'] ?? '') === 'percent' ? ($item['discountValue'] ?? 0).'%' : self::price(['price'.(($item['displayCurrency'] ?? 'USD') === 'IDR' ? 'Idr' : 'Usd') => $item['discountValue'] ?? 0], ($item['displayCurrency'] ?? 'USD') === 'IDR' ? 'id' : 'us', 'price'),
                $item['code'] ?? '',
                ! empty($item['maximumDiscount']) ? str_replace('{amount}', self::price(['price'.(($item['displayCurrency'] ?? 'USD') === 'IDR' ? 'Idr' : 'Usd') => $item['maximumDiscount']], ($item['displayCurrency'] ?? 'USD') === 'IDR' ? 'id' : 'us', 'price'), $t['promotionsUpTo']) : '',
                ! empty($item['endsAt']) ? str_replace('{date}', self::date($item['endsAt'], $language), $t['promotionsUntil']) : '',
            ]),
            'url' => $item['ctaUrl'] ?? '', 'headingLevel' => 3,
        ])->all()) + ['text' => $t['promotionsText']];
        $sections[] = self::section($t['destinationTitle'], collect($props['destinations'] ?? [])->map(fn ($item) => [
            'title' => $item['name'] ?? '',
            'text' => self::localized($item['copy'] ?? null, $language),
            'image' => self::image($item['image'] ?? null, $item['name'] ?? ''),
            'headingLevel' => 3,
        ])->all()) + ['text' => $t['destinationText']];
        $sections[] = self::section($t['packagesTitle'], self::cards($props['featuredRoutes'] ?? [], 'routes', $language)) + ['text' => $t['packagesText']];
        $sections[] = self::section($t['whyTitle'], collect($home['whyChooseItems'] ?? [])->map(fn ($item) => [
            'title' => self::localized($item['title'] ?? null, $language),
            'text' => self::localized($item['text'] ?? null, $language),
            'headingLevel' => 3,
        ])->all()) + ['text' => $t['whyText']];
        $sections[] = self::section($t['reviewsTitle'], collect($props['reviews'] ?? $public['reviews'] ?? [])->map(fn ($item) => [
            'title' => $item['name'] ?? '',
            'text' => self::words([self::localized($item['origin'] ?? null, $language), self::localized($item['text'] ?? null, $language), self::localized($item['source'] ?? null, $language)]),
        ])->all()) + ['text' => $t['reviewsText']];
        $sections[] = self::section($t['guidesTitle'], self::cards($props['latestArticles'] ?? [], 'news', $language)) + ['text' => $t['guidesText']];
        $sections[] = self::questions($t['faqTitle'], $props['faqs'] ?? [], $language);
        $sections[] = self::section($t['availableOnTitle'], collect($public['platformLinks'] ?? [])->map(fn ($item) => ['title' => $item['name'], 'url' => $item['url']])->all());
        $sections[] = ['title' => $t['ctaTitle'], 'text' => $t['ctaText'], 'items' => []];

        return ['h1' => $t['heroTitle'], 'sections' => $sections, 'links' => []];
    }

    private static function routesIndex(array $props, string $language, array $seo): array
    {
        $t = self::copy($language);
        $cards = self::cards(data_get($props, 'routes.data', []), 'routes', $language);
        foreach ($cards as &$card) {
            // Catalog cards hide their introductory copy in the browser.
            $card['text'] = '';
        }

        unset($card);

        $destination = $props['destinationFilter'] ?? 'all';
        $name = collect($props['destinations'] ?? [])->first(fn ($item) => ($item['slug'] ?? $item['id'] ?? '') === $destination)['name'] ?? $destination;
        $introKey = ['bromo' => 'routeIntroBromo', 'jogja' => 'routeIntroJogja', 'tumpak-sewu' => 'routeIntroTumpakSewu', 'medan' => 'routeIntroMedan'][$destination] ?? 'routeIntroAll';

        return ['eyebrow' => $t['packagesAndRoutes'], 'h1' => $destination === 'all' ? $t['routePageTitleAll'] : $t['routePageTitleDestination'].' '.$name, 'intro' => $t[$introKey],
            'sections' => [['title' => '', 'items' => $cards], ['title' => '', 'text' => data_get($props, 'routes.total', count($cards)).' '.$t['packageCount'].'. '.$t['routePriceNote'], 'items' => []]], 'links' => self::pagination($props['routes'] ?? [])];
    }

    private static function routeDetail(array $props, string $language, array $seo): ?array
    {
        $route = $props['route'] ?? null;
        if (! is_array($route)) {
            return null;
        }
        $t = self::copy($language);
        $package = $route['packageOptions'][0] ?? [];
        $title = self::localized($route['title'] ?? null, $language);
        $sections = [
            ['title' => '', 'items' => self::simpleItems([
                $route['badge'] ?? '', $route['duration'] ?? '', $route['pickupLabel'] ?? '', $route['groupType'] ?? '', $route['destinationName'] ?? '', $route['operator'] ?? '', $route['reviewSource'] ?? '', (string) ($route['rating'] ?? ''), (string) ($route['reviewCount'] ?? ''),
            ], $language)],
            self::section($t['routeHighlights'], self::simpleItems($route['highlights'] ?? [], $language)),
            ['title' => self::localized($package['title'] ?? null, $language) ?: $title,
                'text' => self::localized($package['description'] ?? null, $language) ?: self::localized($route['intro'] ?? null, $language),
                'items' => [
                    ['text' => $t['dateFlexible']],
                    ['text' => self::price($package + $route, $language).' '.$t['perPerson'].' '.$t['priceVariesByGroupSize']],
                    ['text' => self::words([self::localized($package['pickupLabel'] ?? null, $language), self::localized($package['groupType'] ?? null, $language)])],
                ]],
        ];
        if (! empty($route['addOns'])) {
            $sections[] = self::section($t['packageOptions'], collect($route['addOns'])->map(fn ($item) => [
                'title' => self::localized($item['title'] ?? null, $language),
                'text' => self::words([self::localized($item['description'] ?? null, $language), self::price($item, $language, 'price').' '.($item['pricing'] === 'perPax' ? $t['perPax'] : $t['perBooking'])]),
            ])->all());
        }
        $gallery = $route['gallery'] ?? [];
        if ($gallery) {
            $sections[] = ['title' => '', 'items' => collect($gallery)->map(fn ($src) => ['image' => self::image($src, self::localized($route['imageAlt'] ?? $route['title'] ?? null, $language))])->all()];
        }
        foreach (['itinerary', 'pickupDetails', 'includes', 'excludes', 'goodToKnow', 'details'] as $key) {
            $sections[] = self::section($t[$key], self::simpleItems($route[$key] ?? [], $language)) + ['headingLevel' => 3];
        }
        foreach (['cancellation', 'confirmation'] as $key) {
            $sections[] = self::section($t[$key.'Policy'], self::simpleItems($route['policies'][$key] ?? [], $language)) + ['headingLevel' => 3];
        }
        $today = now()->toDateString();
        $matches = collect($route['availabilityRules'] ?? [])->filter(fn ($rule) => ! empty($rule['startDate']) && $rule['startDate'] <= $today && (! empty($rule['openEnded']) || ($rule['endDate'] ?? $rule['startDate']) >= $today));
        $closure = $matches->firstWhere('scope', 'package') ?? $matches->firstWhere('scope', 'destination');
        if (($closure['status'] ?? '') === 'blocked') {
            array_unshift($sections, ['title' => '', 'text' => $t['temporaryClosure'].' '.($closure['reason'] ?? ''), 'items' => []]);
        }
        $sections[] = self::section($t['travelerProof'], collect($route['testimonials'] ?? [])->map(fn ($item) => [
            'title' => $item['name'] ?? '',
            'text' => self::words([self::localized($item['quote'] ?? null, $language), self::localized($item['meta'] ?? null, $language)]),
        ])->all());
        $sections[] = self::section($language === 'id' ? 'Artikel terkait rute ini' : 'Articles related to this route', self::cards($props['relatedArticles'] ?? [], 'news', $language));
        $sections[] = self::questions('FAQ General – Tinggal Jalan Tours', $props['faqs'] ?? [], $language);

        return ['eyebrow' => $t['routeDetailEyebrow'], 'h1' => $title,
            'intro' => self::localized($route['why'] ?? null, $language),
            'image' => self::image($route['image'] ?? null, self::localized($route['imageAlt'] ?? $route['title'] ?? null, $language)),
            'sections' => $sections, 'links' => []];
    }

    private static function date(string $date, string $language): string
    {
        $formatter = new \IntlDateFormatter(['id' => 'id_ID', 'cn' => 'zh_CN'][$language] ?? 'en_US', \IntlDateFormatter::LONG, \IntlDateFormatter::NONE);

        return $formatter->format(strtotime($date));
    }

    private static function price(array $item, string $language, string $prefix = 'basePrice'): string
    {
        $currency = $language === 'id' ? 'IDR' : 'USD';
        $value = $item[$prefix.($currency === 'USD' ? 'Usd' : 'Idr')] ?? $item[$prefix] ?? 0;
        $formatter = new \NumberFormatter($currency === 'USD' ? 'en_US' : 'id_ID', \NumberFormatter::CURRENCY);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $currency === 'USD' ? 2 : 0);

        return $formatter->formatCurrency((float) $value, $currency);
    }

    private static function questions(string $title, array $items, string $language): array
    {
        return self::section($title, collect($items)->map(fn ($item) => [
            'title' => self::localized($item['question'] ?? null, $language),
            'text' => self::localized($item['answer'] ?? null, $language),
        ])->all());
    }

    private static function newsIndex(array $props, string $language, array $seo): array
    {
        $t = self::copy($language);
        $articles = data_get($props, 'articles.data', []);
        if (! empty($props['featured']) && ! collect($articles)->contains('slug', $props['featured']['slug'])) {
            array_unshift($articles, $props['featured']);
        }

        return ['eyebrow' => $t['newsEyebrow'], 'h1' => $t['newsTitle'], 'intro' => $t['newsDescription'],
            'sections' => [['title' => '', 'items' => self::cards($articles, 'news', $language)], ['title' => $t['newsCtaHeading'], 'text' => $t['newsNeedAdviceText'], 'items' => []]],
            'links' => self::pagination($props['articles'] ?? [])];
    }

    private static function newsDetail(array $props, string $language, array $seo): ?array
    {
        $article = $props['article'] ?? null;

        if (! is_array($article)) {
            return null;
        }

        $t = self::copy($language);
        $sections = collect($article['sections'] ?? [])->map(fn ($item) => [
            'title' => self::localized($item['heading'] ?? null, $language),
            'text' => self::localized($item['body'] ?? null, $language),
            'id' => preg_replace('/[^a-z0-9]+/', '-', strtolower(self::localized($item['heading'] ?? null, 'us'))),
            'items' => [],
        ])->all();
        $relatedRoutes = collect($props['relatedRoutes'] ?? [])->map(fn ($item) => [
            'title' => self::localized($item['title'] ?? null, $language),
            'text' => self::localized($item['bestFor'] ?? $item['excerpt'] ?? null, $language),
            'url' => '/routes/'.($item['slug'] ?? $item['id']),
        ])->all();

        return [
            'eyebrow' => self::localized($article['destinationName'] ?? null, $language, 'Indonesia travel guide'),
            'h1' => self::localized($article['title'] ?? null, $language, data_get($seo, 'title')),
            'intro' => self::localized($article['excerpt'] ?? null, $language),
            'image' => self::image($article['coverImage'] ?? null, self::localized($article['coverAlt'] ?? $article['title'] ?? null, $language)),
            'sections' => array_merge($sections, [
                ['title' => $t['newsNeedAdviceHeading'], 'text' => $t['newsNeedAdviceText'], 'items' => []],
                self::section('', $relatedRoutes),
                self::section($t['newsReadMoreGuides'], self::cards($props['relatedArticles'] ?? [], 'news', $language)),
            ]),
            'links' => self::coreLinks(),
        ];
    }

    private static function about(array $props, string $language, array $seo): array
    {
        $t = self::copy($language);
        $page = $props['aboutPage'] ?? [];
        $hero = $page['hero'] ?? [];
        $visibility = $page['sectionVisibility'] ?? [];
        $sections = [];
        foreach (['team', 'story', 'workflow', 'milestones', 'values', 'profile', 'cta'] as $key) {
            if (($visibility[$key] ?? true) === false) {
                continue;
            }
            $content = $page[$key] ?? [];
            $items = match ($key) {
                'team' => collect($props['teamMembers'] ?? [])->map(fn ($member) => [
                    'title' => $member['name'] ?? '',
                    'text' => self::words([self::localized($member['role'] ?? null, $language), self::localized($member['biography'] ?? null, $language), $member['location'] ?? '', implode(', ', $member['languages'] ?? [])]),
                    'url' => $member['profileUrl'] ?? null,
                    'image' => self::image($member['portrait'] ?? null, self::localized($member['portraitAlt'] ?? null, $language)),
                    'headingLevel' => ($member['category'] ?? '') === 'field' ? 4 : 3,
                ])->all(),
                'milestones' => collect($props['milestones'] ?? [])->map(fn ($item) => [
                    'title' => self::localized($item['title'] ?? null, $language),
                    'text' => self::words([self::localized($item['period'] ?? null, $language), self::localized($item['description'] ?? null, $language)]),
                    'image' => self::image($item['image'] ?? null, self::localized($item['imageAlt'] ?? null, $language)),
                    'headingLevel' => 3,
                ])->all(),
                'workflow', 'values' => collect($content[$key === 'workflow' ? 'steps' : 'items'] ?? [])->map(fn ($item) => [
                    'title' => self::localized($item['title'] ?? null, $language),
                    'text' => self::localized($item['text'] ?? null, $language), 'headingLevel' => 3,
                ])->all(),
                default => [],
            };
            if (in_array($key, ['team', 'milestones', 'workflow', 'values'], true) && ! $items) {
                continue;
            }
            if ($key === 'profile') {
                foreach (['legal_name', 'founding_year', 'registration'] as $field) {
                    if (! empty($content['show_'.$field]) && ! empty($content[$field])) {
                        $items[] = ['title' => self::localized($content[$field.'_label'] ?? null, $language), 'text' => (string) $content[$field]];
                    }
                }
                $contact = $props['publicData']['site']['contactDetails'] ?? [];
                $areas = $props['publicData']['site']['serviceAreas'] ?? [];
                foreach ([$t['aboutOffice'] => $contact['address'] ?? '', $t['aboutOperatingAreas'] => implode(', ', $areas), $t['aboutTravelStyle'] => $t['aboutTravelStyleValue'], $t['aboutMainServices'] => $t['aboutMainServicesValue']] as $label => $text) {
                    $items[] = ['title' => $label, 'text' => $text];
                }
                $items = array_merge($items, collect($props['platformLinks'] ?? [])->map(fn ($item) => ['title' => $item['name'], 'url' => $item['url']])->all());
            }
            $sections[] = [
                'title' => self::localized($content['title'] ?? null, $language),
                'text' => self::localized($content[$key === 'story' ? 'body' : ($key === 'profile' ? 'operating_description' : ($key === 'cta' ? 'text' : 'intro'))] ?? null, $language),
                'quote' => $key === 'story' ? self::localized($content['quote'] ?? null, $language) : '',
                'quoteAuthor' => $content['quote_author'] ?? '',
                'image' => $key === 'story' ? self::image($content['image'] ?? null, self::localized($content['image_alt'] ?? null, $language)) : null,
                'items' => $items,
                'ordered' => in_array($key, ['workflow', 'milestones'], true),
                'links' => $key === 'cta' ? self::actionLinks($content, $language, ['primary_', 'secondary_']) : [],
            ];
        }

        return ['eyebrow' => self::localized($hero['eyebrow'] ?? null, $language),
            'h1' => self::localized($hero['title'] ?? null, $language),
            'intro' => self::localized($hero['intro'] ?? null, $language),
            'image' => self::image($hero['image'] ?? null, self::localized($hero['image_alt'] ?? null, $language)),
            'sections' => array_merge([['title' => '', 'items' => collect($hero['facts'] ?? [])->map(fn ($fact) => ['title' => self::localized($fact['label'] ?? null, $language), 'text' => self::localized($fact['value'] ?? null, $language)])->all()]], $sections), 'links' => []];
    }

    private static function actionLinks(array $content, string $language, array $prefixes): array
    {
        return collect($prefixes)->map(fn ($prefix) => [
            'label' => self::localized($content[$prefix.(str_ends_with($prefix, '_') ? 'label' : 'Label')] ?? null, $language),
            'url' => $content[$prefix.(str_ends_with($prefix, '_') ? 'url' : 'Url')] ?? '',
        ])->filter(fn ($item) => filled($item['label']) && filled($item['url']))->values()->all();
    }

    private static function privacy(string $language): array
    {
        $t = self::copy($language);
        $sections = collect(['Summary', 'Required', 'Advertising', 'Google', 'Choice', 'Retention', 'Contact'])
            ->map(fn ($key) => ['title' => $t['privacy'.$key.'Title'], 'text' => $t['privacy'.$key.'Text'], 'items' => []])->all();

        return ['eyebrow' => $t['privacyEyebrow'], 'h1' => $t['privacyTitle'],
            'intro' => $t['privacyIntro'], 'sections' => $sections, 'links' => []];
    }

    private static function pagination(array $page): array
    {
        return collect($page['links'] ?? [])->filter(fn ($link) => filled($link['url'] ?? null))->map(fn ($link) => [
            'label' => trim(strip_tags(html_entity_decode($link['label'] ?? ''))), 'url' => $link['url'],
        ])->all();
    }

    private static function section(string $title, array $items): array
    {
        return [
            'title' => $title,
            'requiresItems' => true,
            'items' => collect($items)
                ->filter(fn ($item): bool => filled($item['title'] ?? null) || filled($item['text'] ?? null))
                ->values()
                ->all(),
        ];
    }

    private static function simpleItems(array $items, string $language): array
    {
        if (! array_is_list($items)) {
            $items = $items[$language] ?? $items['us'] ?? $items['en'] ?? $items['id'] ?? $items['cn'] ?? [];
        }

        return collect(is_array($items) ? $items : [$items])->map(fn ($item) => ['text' => self::localized($item, $language)])->all();
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
        return trim(PublicSite::localized($value, $language, $fallback));
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
            ['label' => 'Privacy & cookies', 'url' => '/privacy-policy'],
        ];
    }
}
