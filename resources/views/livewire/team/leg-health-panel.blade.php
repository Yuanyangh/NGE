<div class="bg-white overflow-hidden shadow rounded-lg p-5">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <h3 class="text-lg font-medium text-gray-900">Leg Health</h3>

        <div class="inline-flex rounded-md shadow-sm" role="group" aria-label="Select period">
            <button
                type="button"
                wire:click="setDay"
                class="relative inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-l-md border focus:z-10 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer transition-colors duration-150
                    {{ $period === 'day'
                        ? 'bg-indigo-600 border-indigo-600 text-white'
                        : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' }}"
                aria-pressed="{{ $period === 'day' ? 'true' : 'false' }}"
            >Today</button>
            <button
                type="button"
                wire:click="setWeek"
                class="relative inline-flex items-center px-3 py-1.5 text-sm font-medium border-t border-b focus:z-10 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer transition-colors duration-150
                    {{ $period === 'week'
                        ? 'bg-indigo-600 border-indigo-600 text-white'
                        : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' }}"
                aria-pressed="{{ $period === 'week' ? 'true' : 'false' }}"
            >This Week</button>
            <button
                type="button"
                wire:click="setMonth"
                class="relative inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-r-md border focus:z-10 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer transition-colors duration-150
                    {{ $period === 'month'
                        ? 'bg-indigo-600 border-indigo-600 text-white'
                        : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' }}"
                aria-pressed="{{ $period === 'month' ? 'true' : 'false' }}"
            >This Month</button>
        </div>
    </div>

    <div wire:loading class="flex items-center gap-2 mb-3 text-sm text-indigo-600">
        <svg class="animate-spin h-4 w-4 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        <span>Updating...</span>
    </div>

    @if($stats && count($stats['legs']) > 0)
        @php
            $maxVolume = collect($stats['legs'])->max(fn ($l) => (float) $l['volume']) ?: 1;
        @endphp

        <div class="mt-4 space-y-3">
            @foreach($stats['legs'] as $leg)
                @php
                    $pct = $maxVolume > 0 ? min(100, ((float) $leg['volume'] / $maxVolume) * 100) : 0;
                    $colorClass = match($leg['health_label']) {
                        'strong' => 'bg-green-500',
                        'moderate' => 'bg-yellow-500',
                        default => 'bg-red-400',
                    };
                    $labelClass = match($leg['health_label']) {
                        'strong' => 'text-green-700 bg-green-100',
                        'moderate' => 'text-yellow-700 bg-yellow-100',
                        default => 'text-red-700 bg-red-100',
                    };
                @endphp
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm font-medium text-gray-700">
                            {{ $leg['leg_root_name'] }}
                            @if($leg['is_large_leg']) <span class="text-xs text-gray-400">(Large Leg)</span> @endif
                        </span>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-600">{{ number_format((float) $leg['volume'], 0) }} XP</span>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $labelClass }}">
                                {{ ucfirst($leg['health_label']) }}
                            </span>
                        </div>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="{{ $colorClass }} h-2 rounded-full" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($stats['qvv_capping_warning'])
            <div class="mt-4 rounded-md bg-amber-50 p-3">
                <p class="text-sm text-amber-800">
                    Your large leg is capping your QVV. Focus on growing your smaller legs for better viral rewards.
                </p>
            </div>
        @endif
    @else
        <p class="mt-3 text-sm text-gray-500">No legs found. Start building your team!</p>
    @endif
</div>
