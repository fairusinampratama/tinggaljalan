@php
    $language = data_get($page ?? [], 'props.language', app()->getLocale());
    $htmlLang = match ($language) {
        'us', 'en' => 'en',
        'cn', 'zh' => 'zh-CN',
        default => 'id',
    };
@endphp
<!DOCTYPE html>
<html lang="{{ $htmlLang }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" type="image/png" href="/images/logo-tj.png">

        @include('partials.server-seo')

        @if (request()->routeIs('home'))
            @include('partials.hero-preloads')
        @endif

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.jsx'])
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
