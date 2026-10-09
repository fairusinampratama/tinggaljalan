@php
    $desktopSrc = $desktopSrc ?? $src;
    $sizes = $sizes ?? '100vw';
    $variants = fn ($path) => preg_match('#^/(?:images|storage)/.*(?<!\.svg)$#i', $path) ? \App\Support\ResponsiveImage::srcSet(explode('?', $path)[0]) : null;
@endphp
<picture>
    @if ($desktopSrc !== $src)
        @if ($variants($desktopSrc))<source media="(min-width: 640px)" type="image/webp" srcset="{{ $variants($desktopSrc) }}" sizes="{{ $sizes }}">@endif
        <source media="(min-width: 640px)" srcset="{{ $desktopSrc }}">
    @endif
    @if ($variants($src))<source type="image/webp" srcset="{{ $variants($src) }}" sizes="{{ $sizes }}">@endif
    <img src="{{ $src }}" alt="{{ $alt }}" class="{{ $imageClass }}" style="object-position: {{ $focalPosition ?? 'center' }}" width="{{ $width ?? 1600 }}" height="{{ $height ?? 900 }}" loading="{{ $loading ?? 'lazy' }}" fetchpriority="{{ $priority ?? 'auto' }}">
</picture>
