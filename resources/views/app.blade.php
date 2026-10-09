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
        @include('partials.google-ads-consent')
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
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
            @if (data_get($page, 'component') === 'HomePage')
                <script>
                    // Module scripts delay the browser's native initial fragment
                    // scroll. The target already exists, so align it before paint.
                    try {
                        const id = decodeURIComponent(window.location.hash.slice(1));
                        if (id === 'destination' || id === 'contact') {
                            document.documentElement.dataset.initialFragment = id;
                            const align = () => document.getElementById(id)?.scrollIntoView({ behavior: 'instant' });
                            requestAnimationFrame(align);
                            // FontFaceSet.ready can wait for this deliberately delayed
                            // document's module scripts in Firefox and WebKit. Load
                            // the fonts used here independently of document readiness.
                            const fonts = new Set([...document.querySelectorAll('.server-seo-content h2, .server-seo-content h3, .server-seo-content p, .server-seo-content label, .server-seo-content select, .server-seo-content a')].map((element) => {
                                const style = getComputedStyle(element);
                                return `${style.fontWeight} ${style.fontSize} ${style.fontFamily}`;
                            }));
                            Promise.all([...fonts].map((font) => document.fonts.load(font))).then(() => {
                                if (document.querySelector('.server-seo-content')) requestAnimationFrame(align);
                            }).catch(() => { /* Failed fonts retain the initial native alignment. */ });
                        }
                    } catch { /* An invalid fragment must not affect readability. */ }
                </script>
            @endif
        @endif
    </body>
</html>
