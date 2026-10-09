@php
    $t = \App\Support\ServerPageContent::copy($serverContent['language']);
    $public = data_get($page, 'props.publicData', []);
    $navLinks = [
        ['label' => $t['nav'][1], 'url' => '/#destination'],
        ['label' => $t['nav'][2], 'url' => '/routes'],
        ['label' => $t['nav'][3], 'url' => '/news'],
    ];
    if (! empty($public['site']['aboutEnabled'])) $navLinks[] = ['label' => $t['footerAbout'], 'url' => '/about-us'];
    $navLinks[] = ['label' => $t['nav'][5], 'url' => '/#contact'];
@endphp
<nav class="fixed inset-x-0 top-0 z-50 border-b border-line bg-surface/95 backdrop-blur-xl" aria-label="Site navigation">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:h-[72px] sm:px-8 lg:px-10">
        <a href="/" aria-label="Tinggal Jalan home"><img src="{{ $public['site']['logoUrl'] ?? '/images/logo-tj.png' }}" alt="Tinggal Jalan" width="40" height="40" class="h-9 w-auto object-contain sm:h-10"></a>
        <div class="hidden items-center gap-5 text-sm font-bold lg:flex">
            @foreach ($navLinks as $link)<a href="{{ $link['url'] }}" class="border-b-2 border-transparent py-2">{{ $link['label'] }}</a>@endforeach
            <a href="/booking" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-primary px-5 py-2 text-sm font-bold text-white">{{ $t['footerBookTrip'] }}</a>
        </div>
        <div class="flex items-center gap-2">
            <div class="hidden rounded-full border border-line bg-surface p-1 sm:flex">
                @foreach (['id' => 'ID', 'cn' => '中文', 'us' => 'EN'] as $code => $label)
                    <a href="/language/{{ $code }}" class="rounded-full px-3 py-1.5 text-xs font-bold {{ $serverContent['language'] === $code ? 'bg-secondary text-white' : 'text-muted' }}">{{ $label }}</a>
                @endforeach
            </div>
            <details class="relative lg:hidden">
                <summary aria-label="Open menu" class="grid h-9 w-9 cursor-pointer place-items-center rounded-lg border border-line text-ink">☰</summary>
                <div class="absolute right-0 top-11 grid min-w-56 gap-3 rounded-xl border border-line bg-surface p-4 shadow-soft">
                    @foreach ($navLinks as $link)<a href="{{ $link['url'] }}">{{ $link['label'] }}</a>@endforeach
                    <a href="/booking">{{ $t['footerBookTrip'] }}</a>
                    @foreach (['id' => 'ID', 'cn' => '中文', 'us' => 'EN'] as $code => $label)<a href="/language/{{ $code }}">{{ $label }}</a>@endforeach
                </div>
            </details>
        </div>
    </div>
</nav>
