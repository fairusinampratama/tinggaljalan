@php
    use App\Support\PublicSite;
    use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

    $resolveImage = static function (mixed $state): ?string {
        if (is_array($state)) {
            $state = collect($state)->first();
        }

        if ($state instanceof TemporaryUploadedFile) {
            try {
                return $state->temporaryUrl();
            } catch (Throwable) {
                return null;
            }
        }

        return filled($state) ? PublicSite::assetPath((string) $state) : null;
    };

    $mode = $get('data.preview_mode', true) ?: 'desktop';
    $desktopImage = $resolveImage($get('data.desktop_image', true));
    $mobileImage = $resolveImage($get('data.mobile_image', true));
    $image = $mode === 'mobile' ? ($mobileImage ?: $desktopImage) : $desktopImage;
    $alignment = $get('data.text_alignment', true) ?: 'left';
    $focalPosition = $get('data.focal_position', true) ?: 'center';
    $overlay = (int) ($get('data.overlay_strength', true) ?? 40);
    $heading = data_get($get('data.heading', true), 'us');
    $eyebrow = data_get($get('data.eyebrow', true), 'us');
    $description = data_get($get('data.description', true), 'us');
    $primaryLabel = data_get($get('data.primary_cta_label', true), 'us');
    $secondaryLabel = data_get($get('data.secondary_cta_label', true), 'us');
    $hasText = filled($heading) || filled($eyebrow) || filled($description) || filled($primaryLabel) || filled($secondaryLabel);
    $opacity = max(0, min(100, $overlay)) / 100;

    $gradient = match ($alignment) {
        'right' => "linear-gradient(to left, rgba(0,0,0,{$opacity}) 0%, rgba(0,0,0,".($opacity * .78).") 42%, transparent 100%)",
        'center' => "linear-gradient(rgba(0,0,0,".($opacity * .8)."), rgba(0,0,0,".($opacity * .8)."))",
        default => "linear-gradient(to right, rgba(0,0,0,{$opacity}) 0%, rgba(0,0,0,".($opacity * .78).") 42%, transparent 100%)",
    };

    $contentStyle = match ($alignment) {
        'right' => 'align-items:flex-end;text-align:right;margin-left:auto;',
        'center' => 'align-items:center;text-align:center;margin-inline:auto;',
        default => 'align-items:flex-start;text-align:left;',
    };
@endphp

<div class="space-y-3">
    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-gray-950 dark:text-white">Live preview</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">English content ? {{ ucfirst($mode) }}</p>
        </div>
        @if ($mode === 'mobile' && blank($mobileImage) && filled($desktopImage))
            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-amber-600/20 dark:bg-amber-400/10 dark:text-amber-300">
                Desktop fallback
            </span>
        @endif
    </div>

    <div
        @class([
            'relative mx-auto overflow-hidden rounded-2xl bg-gray-950 shadow-xl ring-1 ring-black/10',
            'aspect-[4/5] max-w-[19rem]' => $mode === 'mobile',
            'aspect-[16/7] w-full' => $mode === 'desktop',
        ])
    >
        @if ($image)
            <img
                src="{{ $image }}"
                alt=""
                class="absolute inset-0 h-full w-full object-cover"
                style="object-position: {{ $focalPosition }}"
            />
        @else
            <div class="absolute inset-0 grid place-items-center bg-gray-900 px-6 text-center text-sm text-gray-400">
                Upload artwork to preview the slide.
            </div>
        @endif

        <div class="absolute inset-0" style="background: {{ $gradient }}"></div>

        @if ($hasText)
            <div class="absolute inset-0 flex p-5 sm:p-7">
                <div class="flex h-full w-full max-w-lg flex-col justify-center" style="{{ $contentStyle }}">
                    @if ($eyebrow)
                        <p class="mb-2 border-l-2 border-amber-400 pl-3 text-[9px] font-semibold uppercase tracking-[0.16em] text-amber-300">
                            {{ $eyebrow }}
                        </p>
                    @endif
                    @if ($heading)
                        <p class="font-serif text-2xl font-semibold leading-tight text-white {{ $mode === 'desktop' ? 'sm:text-3xl' : '' }}">
                            {{ $heading }}
                        </p>
                    @endif
                    @if ($description)
                        <p class="mt-2 line-clamp-3 text-xs font-medium leading-5 text-white/90">
                            {{ $description }}
                        </p>
                    @endif
                    @if ($primaryLabel || $secondaryLabel)
                        <div class="mt-4 flex flex-wrap gap-2">
                            @if ($primaryLabel)
                                <span class="rounded-lg bg-white px-3 py-2 text-[10px] font-semibold text-gray-950">{{ $primaryLabel }}</span>
                            @endif
                            @if ($secondaryLabel)
                                <span class="rounded-lg border border-white/40 bg-white/10 px-3 py-2 text-[10px] font-semibold text-white">{{ $secondaryLabel }}</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    @if ($hasText && $overlay === 0)
        <div class="rounded-xl bg-amber-50 p-3 text-xs leading-5 text-amber-800 ring-1 ring-amber-600/20 dark:bg-amber-400/10 dark:text-amber-200">
            Text readability may be weak with no overlay. Use at least Light unless the artwork already contains a dark text-safe area.
        </div>
    @endif

    <p class="text-xs leading-5 text-gray-500 dark:text-gray-400">
        The public hero uses the same image, focal point, alignment, and overlay. Exact width varies by visitor screen.
    </p>
</div>
