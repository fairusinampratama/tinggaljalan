@php
    $serverContent = \App\Support\ServerPageContent::fromInertiaPage($page ?? []);
    $t = \App\Support\ServerPageContent::copy($serverContent['language'] ?? 'us');
    $public = data_get($page, 'props.publicData', []);
@endphp

@if ($serverContent)
    <main class="server-seo-content" aria-label="{{ $serverContent['h1'] }}">
        @include('partials.server-navigation')
        @if ($serverContent['component'] === 'HomePage')
            @include('partials.server-home-content')
        @else
        <div class="server-document">
        @if ($serverContent['eyebrow'])
            <p>{{ $serverContent['eyebrow'] }}</p>
        @endif
        <h1>{{ $serverContent['h1'] }}</h1>
        @include('partials.plain-text', ['text' => $serverContent['intro']])
        @if ($serverContent['image'])
            <img src="{{ $serverContent['image']['src'] }}" alt="{{ $serverContent['image']['alt'] }}" loading="eager" width="1200" height="800">
        @endif

        @include('partials.server-sections', ['sections' => $serverContent['sections']])

        @if (! empty($serverContent['links']))
            <nav aria-label="Site navigation">
                <ul>
                    @foreach ($serverContent['links'] as $link)
                        <li><a href="{{ $link['url'] }}">{{ $link['label'] }}</a></li>
                    @endforeach
                </ul>
            </nav>
        @endif
        </div>
        @endif
    </main>
@endif
