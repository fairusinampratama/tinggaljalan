@php
    $serverContent = \App\Support\ServerPageContent::fromInertiaPage($page ?? []);
@endphp

@if ($serverContent)
    <main class="server-seo-content" aria-label="{{ $serverContent['h1'] }}">
        @if ($serverContent['eyebrow'])
            <p>{{ $serverContent['eyebrow'] }}</p>
        @endif
        <h1>{{ $serverContent['h1'] }}</h1>
        @if ($serverContent['intro'])
            <p>{{ $serverContent['intro'] }}</p>
        @endif
        @if ($serverContent['image'])
            <img
                src="{{ $serverContent['image']['src'] }}"
                alt="{{ $serverContent['image']['alt'] }}"
                loading="eager"
                width="1200"
                height="800"
            >
        @endif

        @foreach ($serverContent['sections'] as $section)
            @continue(blank($section['title'] ?? null) || empty($section['items'] ?? []))
            <section>
                <h2>{{ $section['title'] }}</h2>
                <ul>
                    @foreach ($section['items'] as $item)
                        <li>
                            @if (! empty($item['image']))
                                <img
                                    src="{{ $item['image']['src'] }}"
                                    alt="{{ $item['image']['alt'] }}"
                                    loading="lazy"
                                    width="800"
                                    height="600"
                                >
                            @endif
                            @if (! empty($item['url']) && ! empty($item['title']))
                                <p><a href="{{ $item['url'] }}"><strong>{{ $item['title'] }}</strong></a></p>
                            @elseif (! empty($item['title']))
                                <p><strong>{{ $item['title'] }}</strong></p>
                            @endif
                            @if (! empty($item['text']))
                                <p>{{ $item['text'] }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach

        @if (! empty($serverContent['links']))
            <nav aria-label="Crawler navigation">
                <h2>Explore Tinggal Jalan</h2>
                <ul>
                    @foreach ($serverContent['links'] as $link)
                        <li><a href="{{ $link['url'] }}">{{ $link['label'] }}</a></li>
                    @endforeach
                </ul>
            </nav>
        @endif
    </main>
@endif
