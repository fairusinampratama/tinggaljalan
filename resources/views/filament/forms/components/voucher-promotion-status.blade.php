@php
    $usage = $status['redemption_count'].' / '.($status['usage_limit'] ?? 'Unlimited');
    $schedule = match (true) {
        $status['starts_at'] && $status['ends_at'] => $status['starts_at']->format('M j, Y H:i').' – '.$status['ends_at']->format('M j, Y H:i'),
        (bool) $status['starts_at'] => 'From '.$status['starts_at']->format('M j, Y H:i'),
        (bool) $status['ends_at'] => 'Until '.$status['ends_at']->format('M j, Y H:i'),
        default => 'No date limit',
    };
    $tripScope = $status['applies_to_all_packages']
        ? 'All active trips'
        : $status['active_package_count'].' of '.$status['scoped_package_count'].' selected trips active';
@endphp

<div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
    <div class="flex flex-wrap items-center gap-3">
        <span class="text-sm font-medium text-gray-950 dark:text-white">Current status</span>
        <x-filament::badge :color="$status['color']">{{ $status['label'] }}</x-filament::badge>
        <span class="text-sm text-gray-600 dark:text-gray-300">{{ $status['reason'] }}</span>
    </div>

    @if ($status['reasons'] !== [])
        <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-danger-700 dark:text-danger-300">
            @foreach ($status['reasons'] as $reason)
                <li>{{ $reason }}</li>
            @endforeach
        </ul>
    @endif

    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2 xl:grid-cols-5">
        <div>
            <dt class="font-medium text-gray-950 dark:text-white">Shown to</dt>
            <dd class="mt-1 text-gray-600 dark:text-gray-300">
                {{ $status['audiences'] === [] ? 'No visitor audience' : implode(', ', $status['audiences']) }}
            </dd>
        </div>
        <div>
            <dt class="font-medium text-gray-950 dark:text-white">Schedule</dt>
            <dd class="mt-1 text-gray-600 dark:text-gray-300">{{ $schedule }}</dd>
        </div>
        <div>
            <dt class="font-medium text-gray-950 dark:text-white">Eligible trips</dt>
            <dd class="mt-1 text-gray-600 dark:text-gray-300">{{ $tripScope }}</dd>
        </div>
        <div>
            <dt class="font-medium text-gray-950 dark:text-white">Usage</dt>
            <dd class="mt-1 text-gray-600 dark:text-gray-300">{{ $usage }}</dd>
        </div>
        <div>
            <dt class="font-medium text-gray-950 dark:text-white">Promotion order</dt>
            <dd class="mt-1 text-gray-600 dark:text-gray-300">{{ $status['sort_order'] }} (lower appears first)</dd>
        </div>
    </dl>
</div>
