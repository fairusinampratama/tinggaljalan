@foreach (\App\Support\ResponsiveImage::heroPreloads() as $preload)
    <link
        rel="preload"
        as="image"
        href="{{ $preload['href'] }}"
        imagesrcset="{{ $preload['srcset'] }}"
        imagesizes="{{ $preload['imagesizes'] }}"
        media="{{ $preload['media'] }}"
        fetchpriority="high"
    >
@endforeach
