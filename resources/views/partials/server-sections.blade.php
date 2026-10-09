        @foreach ($sections as $section)
            @continue(! empty($section['requiresItems']) && empty($section['items']))
            @continue(blank($section['text'] ?? null) && empty($section['items'] ?? []) && ! isset($section['id']))
            <section @if (! empty($section['id'])) id="{{ $section['id'] }}" @endif>
                @if (filled($section['title'] ?? null))
                    @if (($section['headingLevel'] ?? 2) === 3)
                        <h3>{{ $section['title'] }}</h3>
                    @else
                        <h2>{{ $section['title'] }}</h2>
                    @endif
                @endif
                @include('partials.plain-text', ['text' => $section['text'] ?? ''])
                @if (! empty($section['image']))
                    <img src="{{ $section['image']['src'] }}" alt="{{ $section['image']['alt'] }}" loading="lazy" width="800" height="600">
                @endif
                @if (! empty($section['quote']))
                    <figure><blockquote>{{ $section['quote'] }}</blockquote><figcaption>{{ $section['quoteAuthor'] }}</figcaption></figure>
                @endif
                @if (! empty($section['items']))
                    @if (! empty($section['ordered']))<ol>@else<ul>@endif
                    @foreach ($section['items'] as $item)
                        <li>
                            @if (! empty($item['image']))
                                <img src="{{ $item['image']['src'] }}" alt="{{ $item['image']['alt'] }}" loading="lazy" width="800" height="600">
                            @endif
                            @php
                                $url = $item['url'] ?? '';
                                $safeUrl = preg_match('#^(?:/(?!/)|https?://|mailto:)#i', $url);
                            @endphp
                            @if (! empty($item['title']))
                                @if (($item['headingLevel'] ?? null) === 3)<h3>@elseif (($item['headingLevel'] ?? null) === 4)<h4>@else<p>@endif
                                @if ($safeUrl)<a href="{{ $url }}">{{ $item['title'] }}</a>@else{{ $item['title'] }}@endif
                                @if (($item['headingLevel'] ?? null) === 3)</h3>@elseif (($item['headingLevel'] ?? null) === 4)</h4>@else</p>@endif
                            @endif
                            @include('partials.plain-text', ['text' => $item['text'] ?? ''])
                            @include('partials.plain-text', ['text' => $item['meta'] ?? ''])
                        </li>
                    @endforeach
                    @if (! empty($section['ordered']))</ol>@else</ul>@endif
                @endif
                @foreach ($section['links'] ?? [] as $link)
                    @if (preg_match('#^(?:/(?!/)|https?://|mailto:)#i', $link['url']))
                        <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                    @endif
                @endforeach
            </section>
        @endforeach
