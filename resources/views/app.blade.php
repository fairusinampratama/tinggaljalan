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
        <link rel="apple-touch-icon" href="/images/logo-tj.png">
        <script>
            document.documentElement.classList.add('js');
        </script>
        <style>
            .js .server-seo-content {
                position: absolute;
                width: 1px;
                height: 1px;
                margin: -1px;
                overflow: hidden;
                clip: rect(0 0 0 0);
                clip-path: inset(50%);
                white-space: nowrap;
            }
        </style>

        @include('partials.server-seo')

        @if (request()->routeIs('home'))
            @include('partials.hero-preloads')
        @endif

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.jsx'])
        @inertiaHead
    </head>
    <body>
        @php
            $__inertiaSsrResponse = app(\Inertia\Ssr\SsrState::class)->setPage($page)->dispatch();
        @endphp

        @if ($__inertiaSsrResponse)
            {!! $__inertiaSsrResponse->body !!}
        @else
            <script data-page="app" type="application/json">{!! json_encode($page) !!}</script>
            <div id="app">
                @include('partials.server-page-content')
            </div>
        @endif
    </body>
</html>
