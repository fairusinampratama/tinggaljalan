        @foreach ($sections as $section)
            @continue(! empty($section['requiresItems']) && empty($section['items']))
            @continue(blank($section['text'] ?? null) && empty($section['items'] ?? []) && ! isset($section['id']))
            <section @class(['route-content-card' => ($serverContent['component'] ?? '') === 'RouteDetailPage' && filled($section['title'] ?? null)]) @if (! empty($section['id'])) id="{{ $section['id'] }}" @endif>
                @if (filled($section['title'] ?? null))
                    @if (($section['headingLevel'] ?? 2) === 3)
                        <h3>{{ $section['title'] }}</h3>
                    @else
                        <h2>{{ $section['title'] }}</h2>
                    @endif
                @endif
                @include('partials.plain-text', ['text' => $section['text'] ?? ''])
                @foreach ($section['images'] ?? [] as $photo)
                    <figure class="news-section-photo">
                        @include('partials.server-responsive-image', [
                            'src' => $photo['src'], 'desktopSrc' => $photo['src'], 'alt' => $photo['alt'],
                            'imageClass' => 'news-inline-photo', 'width' => $photo['width'], 'height' => $photo['height'],
                            'sizes' => '(min-width: 1280px) 768px, (min-width: 1024px) calc(100vw - 452px), calc(100vw - 32px)',
                            'loading' => 'lazy', 'priority' => 'auto',
                        ])
                        @if (filled($photo['caption']))<figcaption>{{ $photo['caption'] }}</figcaption>@endif
                    </figure>
                @endforeach
                @if (! empty($section['image']))
                    <img src="{{ $section['image']['src'] }}" alt="{{ $section['image']['alt'] }}" loading="lazy" width="800" height="600">
                @endif
                @if (! empty($section['quote']))
                    <figure><blockquote>{{ $section['quote'] }}</blockquote><figcaption>{{ $section['quoteAuthor'] }}</figcaption></figure>
                @endif
                @if (! empty($section['timeline']))
                    <ol class="itinerary-timeline mt-5 space-y-6" data-testid="server-itinerary-timeline">
                        @foreach ($section['timeline'] as $step)
                            <li class="relative pl-6">
                                @if (! $loop->last)<span aria-hidden="true" class="absolute bottom-[-1.5rem] left-1 top-2 w-px bg-line"></span>@endif
                                <span aria-hidden="true" class="absolute left-0 top-2 h-[9px] w-[9px] rounded-full bg-secondary"></span>
                                @if (count(array_unique(array_column($section['timeline'], 'day'))) > 1 && ($loop->first || $section['timeline'][$loop->index - 1]['day'] !== $step['day']))
                                    <p class="mb-3 text-sm font-semibold text-secondary">{{ $section['dayLabel'] }} {{ $step['day'] }}{{ $section['dayLabel'] === '第' ? ' 天' : '' }}</p>
                                @endif
                                <div @class(['grid gap-2', 'sm:grid-cols-[7rem_minmax(0,1fr)] sm:gap-5' => filled($step['time'])])>
                                    @if (filled($step['time']))<div><span class="inline-block rounded-md bg-secondary/10 px-2.5 py-1 text-sm font-bold text-secondary">{{ $step['time'] }}</span></div>@endif
                                    <div class="min-w-0 break-words"><p class="font-bold text-primary">{{ $step['title'] }}</p>@include('partials.plain-text', ['text' => $step['description']])</div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @elseif (! empty($section['items']))
                    @if (! empty($section['ordered']))<ol>@else<ul>@endif
                    @foreach ($section['items'] as $item)
                        <li>
                            @if (! empty($item['image']))
                                @if (! empty($item['responsiveImage']))
                                    @include('partials.server-responsive-image', [
                                        'src' => $item['image']['src'], 'desktopSrc' => $item['image']['src'], 'alt' => $item['image']['alt'], 'imageClass' => '',
                                        'sizes' => '(min-width: 1024px) 800px, 100vw',
                                        'loading' => 'lazy', 'width' => 800, 'height' => 600,
                                    ])
                                @else
                                    <img src="{{ $item['image']['src'] }}" alt="{{ $item['image']['alt'] }}" loading="lazy" width="800" height="600">
                                @endif
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
