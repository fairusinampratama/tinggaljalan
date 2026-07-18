<div class="space-y-4">
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl bg-gray-50 px-4 py-3 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Active</p>
            <p class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">{{ $activeCount }}</p>
        </div>
        <div class="rounded-xl bg-gray-50 px-4 py-3 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Inactive</p>
            <p class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">{{ $inactiveCount }}</p>
        </div>
        <div class="rounded-xl bg-gray-50 px-4 py-3 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Sample profiles</p>
            <p class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">{{ $sampleCount }}</p>
        </div>
    </div>

    @if ($records->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-300 p-6 text-center dark:border-white/20">
            <p class="text-sm font-medium text-gray-950 dark:text-white">No team members yet</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Add the first person who should appear on the About page.</p>
            <x-filament::button class="mt-4" tag="a" :href="$createUrl" target="_blank" rel="noopener noreferrer" size="sm">
                Add team member
            </x-filament::button>
        </div>
    @else
        <div class="overflow-x-auto rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
            <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 dark:bg-white/5 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3">Person</th>
                        <th class="px-4 py-3">Division</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-white/10 dark:bg-transparent">
                    @foreach ($records as $record)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($record['portraitUrl'])
                                        <img class="h-10 w-10 rounded-lg object-cover" src="{{ $record['portraitUrl'] }}" alt="" loading="lazy">
                                    @else
                                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100 text-xs font-semibold text-gray-500 dark:bg-white/10 dark:text-gray-300">
                                            {{ $record['initials'] }}
                                        </div>
                                    @endif
                                    <div>
                                        <p class="font-medium text-gray-950 dark:text-white">{{ $record['name'] }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $record['role'] ?: 'No role entered' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record['category'] }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <x-filament::badge :color="$record['isActive'] ? 'success' : 'gray'">
                                        {{ $record['isActive'] ? 'Published' : 'Hidden' }}
                                    </x-filament::badge>
                                    @if ($record['isSample'])
                                        <x-filament::badge color="warning">Sample</x-filament::badge>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400" href="{{ $record['editUrl'] }}" target="_blank" rel="noopener noreferrer">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                @if ($remainingCount > 0)
                    {{ $remainingCount }} more {{ Str::plural('profile', $remainingCount) }} available in the full list.
                @else
                    Showing all team members.
                @endif
            </p>
            <div class="flex flex-wrap gap-2">
                <x-filament::button tag="a" :href="$createUrl" target="_blank" rel="noopener noreferrer" color="gray" size="sm">Add team member</x-filament::button>
                <x-filament::button tag="a" :href="$manageUrl" target="_blank" rel="noopener noreferrer" size="sm">Manage &amp; reorder all</x-filament::button>
            </div>
        </div>
    @endif
</div>
