@php
    $props = $page['props'] ?? [];
    $language = $serverContent['language'];
    $slides = $public['home']['heroSlides'] ?? [];
    if (! $slides) $slides = [['desktopImage' => '/images/hero-bromo.jpg', 'heading' => $t['heroTitle'], 'description' => $t['heroText'], 'primaryCtaLabel' => $t['exploreRoutes'], 'primaryCtaUrl' => '/routes']];
    $sections = $serverContent['sections'];
    $slideCount = count($slides);
    $promotionSection = $sections[$slideCount];
    $destinationSection = $sections[$slideCount + 1];
    $localized = fn ($value) => \App\Support\PublicSite::localized($value, $language);
@endphp
<div class="home-page">
    <section id="home" class="relative bg-canvas pt-0">
        <h1 class="sr-only">{{ $serverContent['h1'] }}</h1>
        {{-- Native horizontal scrolling keeps every slide usable without JavaScript. --}}
        <div class="server-native-carousel relative flex h-[520px] w-full overflow-x-auto bg-primary sm:h-[580px]" tabindex="0" aria-label="{{ $t['heroCarouselLabel'] }}">
            @foreach ($slides as $slide)
                @php
                    $alignment = $slide['textAlignment'] ?? 'left';
                    $alignmentClass = match ($alignment) { 'right' => 'ml-auto items-end text-right', 'center' => 'mx-auto items-center text-center', default => 'items-start text-left' };
                    $overlayClass = match ($alignment) { 'right' => 'bg-gradient-to-l from-black via-black/60 to-transparent', 'center' => 'bg-black', default => 'bg-gradient-to-r from-black via-black/60 to-transparent' };
                @endphp
                <section class="relative h-full w-full shrink-0">
                    @include('partials.server-responsive-image', ['src' => $slide['mobileImage'] ?? $slide['desktopImage'], 'desktopSrc' => $slide['desktopImage'], 'alt' => $localized($slide['imageAlt'] ?? ''), 'imageClass' => 'absolute inset-0 h-full w-full object-cover', 'focalPosition' => $slide['focalPosition'] ?? 'center', 'loading' => $loop->first ? 'eager' : 'lazy', 'priority' => $loop->first ? 'high' : 'auto'])
                    <div class="absolute inset-0 {{ $overlayClass }}" style="opacity: {{ min(100, max(0, $slide['overlayStrength'] ?? 40)) / 100 }}"></div>
                    <div class="absolute inset-0 mx-auto max-w-7xl pt-16 sm:pt-20 lg:px-4">
                        <div class="relative flex h-full max-w-[min(34rem,calc(100%-2rem))] flex-col justify-end px-5 pb-28 pt-24 sm:max-w-[600px] sm:justify-center sm:px-12 sm:pb-0 sm:pt-0 {{ $alignmentClass }}">
                            @if ($localized($slide['eyebrow'] ?? ''))<p class="mb-3 inline-flex border-l-2 border-accent px-3 text-[11px] font-semibold uppercase leading-5 tracking-[0.14em] text-accent sm:mb-4 sm:px-4 sm:text-xs sm:tracking-[0.18em]">{{ $localized($slide['eyebrow']) }}</p>@endif
                            <h2 class="text-balance font-display text-[2.35rem] font-normal leading-[1.04] tracking-normal text-white sm:text-[54px] sm:leading-[1.08]">{{ $localized($slide['heading'] ?? '') }}</h2>
                            <p class="mt-3 text-pretty text-sm font-semibold leading-relaxed text-white/90 sm:mt-4 sm:text-[17px]">{{ $localized($slide['description'] ?? '') }}</p>
                            <div class="mt-6 flex flex-wrap gap-3 sm:mt-8">
                                @foreach (['primaryCta', 'secondaryCta'] as $key)
                                    @if ($localized($slide[$key.'Label'] ?? '') && preg_match('#^(?:/(?!/)|https://|mailto:|tel:)#i', $slide[$key.'Url'] ?? ''))
                                        <a href="{{ $slide[$key.'Url'] }}" class="inline-flex min-h-10 items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold sm:min-h-11 sm:px-5 sm:py-2.5 {{ $key === 'primaryCta' ? 'bg-primary text-white shadow-soft' : 'border border-white/35 bg-surface/10 text-white' }}">{{ $localized($slide[$key.'Label']) }}</a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>
            @endforeach
        </div>
        <div class="relative z-30 mx-auto -mt-7 max-w-6xl px-4 pb-12 sm:-mt-12 sm:px-8 sm:pb-16 lg:px-10">
            <div class="rounded-xl border border-line/80 bg-surface/95 p-5 shadow-xl shadow-black/5 backdrop-blur sm:p-6">
                <p class="font-display text-xl font-normal text-primary sm:text-2xl">{{ $t['searchTitle'] }}</p>
                <p class="mt-1 max-w-2xl text-sm leading-6 text-muted">{{ $t['findTripText'] }}</p>
                <form action="/routes" method="get" class="mt-5 grid gap-3 md:grid-cols-[1fr_1fr_auto] md:items-end">
                    <label><span class="mb-2 block text-sm font-semibold text-ink">{{ $t['destinationFilterLabel'] }}</span><select name="destination" class="h-[46px] w-full rounded-xl border border-line bg-canvas px-4 text-sm font-semibold"><option value="">Select</option>@foreach ($public['destinations'] ?? [] as $destination)<option value="{{ $destination['slug'] ?? $destination['id'] }}" @selected(($public['bookingOptions']['initialBooking']['destination'] ?? '') === $destination['name'])>{{ $destination['name'] }}</option>@endforeach</select></label>
                    <label><span class="mb-2 block text-sm font-semibold text-ink">{{ $t['styleLabel'] }}</span><select name="style" class="h-[46px] w-full rounded-xl border border-line bg-canvas px-4 text-sm font-semibold">@foreach ($public['routeStyles'] ?? [] as $style)<option value="{{ $style['value'] }}">{{ $localized($style['label']) }}</option>@endforeach</select></label>
                    <button class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white shadow-soft sm:min-h-11 sm:px-5 sm:py-2.5 md:min-w-40"><svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m16.24 7.76-2.12 6.36-6.36 2.12 2.12-6.36z"/></svg>{{ $t['exploreTrips'] }}</button>
                </form>
            </div>
        </div>
    </section>
    @if (! empty($promotionSection['items']))
        <section id="promotions" class="overflow-hidden bg-subtle/65 px-4 py-10 sm:px-8 sm:py-12 lg:px-10">
            <div class="mx-auto max-w-7xl">
                <p class="public-eyebrow text-accent">{{ $t['promotionsEyebrow'] }}</p><h2 class="public-heading-section mt-2 text-primary">{{ $promotionSection['title'] }}</h2><p class="public-copy mt-2 max-w-2xl">{{ $promotionSection['text'] }}</p>
                <ul class="mt-6 flex gap-4 overflow-x-auto pb-2">
                    @foreach ($promotionSection['items'] as $item)
                        <li class="flex min-w-[88%] flex-col rounded-2xl border border-secondary/25 bg-white p-4 shadow-soft sm:min-w-[min(40rem,100%)]">
                            <h3 class="text-base font-extrabold leading-6 text-ink">{{ $item['title'] }}</h3><p class="mt-1 text-xs leading-5 text-muted">{{ $item['text'] }}</p><p class="mt-3 text-xs font-bold text-secondary">{{ $item['meta'] }}</p>
                            @if (preg_match('#^/(?!/)#', $item['url']))<a href="{{ $item['url'] }}" class="mt-3 inline-flex min-h-10 items-center rounded-xl border border-line px-4 text-sm font-bold text-secondary">{{ $t['viewRoutes'] }}</a>@endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif
    <section id="destination" class="public-section relative bg-white">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto mb-8 max-w-3xl text-center sm:mb-10"><p class="public-eyebrow text-secondary">{{ $t['destinationEyebrow'] }}</p><h2 class="public-heading-section mt-3 text-primary">{{ $destinationSection['title'] }}</h2><p class="public-copy mx-auto mt-3 max-w-2xl">{{ $destinationSection['text'] }}</p></div>
            <div class="grid items-stretch gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($destinationSection['items'] as $index => $item)
                    <article class="flex h-full flex-col overflow-hidden rounded-xl border border-line bg-surface shadow-soft">
                        @if ($item['image'])
                            @include('partials.server-responsive-image', ['src' => $item['image']['src'], 'desktopSrc' => $item['image']['src'], 'alt' => $item['image']['alt'], 'imageClass' => 'h-52 w-full object-cover', 'width' => 1200, 'height' => 780, 'sizes' => '(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw', 'loading' => 'lazy', 'priority' => 'auto'])
                        @endif
                        <div class="flex min-w-0 flex-1 flex-col p-5"><p class="text-xs font-bold uppercase tracking-[0.04em] text-secondary">{{ $props['destinations'][$index]['region'] ?? '' }}</p><h3 class="public-heading-card mt-2 sm:min-h-16"><a href="{{ $item['url'] }}">{{ $item['title'] }}</a></h3><p class="public-copy mt-3 sm:min-h-[7.5rem]">{{ $item['text'] }}</p><a href="{{ $item['url'] }}" class="mt-auto pt-5 text-right text-sm font-bold text-secondary">{{ $t['viewRoutes'] }} →</a></div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
    <div class="server-document">
        @include('partials.server-sections', ['sections' => array_slice($sections, $slideCount + 2)])
        <nav aria-label="More pages"><ul>@foreach ($serverContent['links'] as $link)<li><a href="{{ $link['url'] }}">{{ $link['label'] }}</a></li>@endforeach</ul></nav>
    </div>
</div>
