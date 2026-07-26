@php
    $seo = data_get($page ?? [], 'props.seo', []);
    $title = data_get($seo, 'title', config('app.name', 'Tinggal Jalan'));
    $description = data_get($seo, 'description');
    $robots = data_get($seo, 'robots');
    $canonical = data_get($seo, 'canonical');
    $type = data_get($seo, 'og_type', data_get($seo, 'type', 'website'));
    $image = data_get($seo, 'image');
    $siteName = \App\Support\Seo::SITE_NAME;
    $twitterCard = data_get($seo, 'twitter_card', 'summary_large_image');
    $publishedTime = data_get($seo, 'published_time');
    $modifiedTime = data_get($seo, 'modified_time');
    $jsonLd = collect(data_get($seo, 'json_ld', []))->filter()->values();
    $hreflangLinks = \App\Support\ServerPageContent::hreflang($page ?? []);
@endphp

<title>{{ $title }}</title>
@if ($description)
    <meta data-inertia="description" name="description" content="{{ $description }}">
@endif
@if ($robots)
    <meta data-inertia="robots" name="robots" content="{{ $robots }}">
@endif
@if ($canonical)
    <link data-inertia="canonical" rel="canonical" href="{{ $canonical }}">
@endif
@foreach ($hreflangLinks as $alternate)
    <link rel="alternate" hreflang="{{ $alternate['hreflang'] }}" href="{{ $alternate['href'] }}">
@endforeach
<meta data-inertia="og:site_name" property="og:site_name" content="{{ $siteName }}">
<meta data-inertia="og:title" property="og:title" content="{{ $title }}">
@if ($description)
    <meta data-inertia="og:description" property="og:description" content="{{ $description }}">
@endif
<meta data-inertia="og:type" property="og:type" content="{{ $type }}">
@if ($canonical)
    <meta data-inertia="og:url" property="og:url" content="{{ $canonical }}">
@endif
@if ($image)
    <meta data-inertia="og:image" property="og:image" content="{{ $image }}">
@endif
@if ($publishedTime)
    <meta data-inertia="article:published_time" property="article:published_time" content="{{ $publishedTime }}">
@endif
@if ($modifiedTime)
    <meta data-inertia="article:modified_time" property="article:modified_time" content="{{ $modifiedTime }}">
@endif
<meta data-inertia="twitter:card" name="twitter:card" content="{{ $twitterCard }}">
<meta data-inertia="twitter:title" name="twitter:title" content="{{ $title }}">
@if ($description)
    <meta data-inertia="twitter:description" name="twitter:description" content="{{ $description }}">
@endif
@if ($image)
    <meta data-inertia="twitter:image" name="twitter:image" content="{{ $image }}">
@endif
@if ($jsonLd->isNotEmpty())
    <script data-inertia="json-ld" type="application/ld+json">{!! json_encode($jsonLd->all(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
@endif
