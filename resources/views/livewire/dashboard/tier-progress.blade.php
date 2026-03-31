<div class="bg-white rounded-xl shadow-sm p-6">
    {{-- Section header --}}
    <div class="flex items-center gap-2 mb-5">
        <svg class="h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
        </svg>
        <h2 class="text-lg font-semibold text-gray-900">Tier Progress</h2>
    </div>

    @if($progress)
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Affiliate Commission Section --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Affiliate Commission</h3>
                @if($progress['current_affiliate_tier'])
                    <span class="inline-flex items-center rounded-lg bg-indigo-100 px-3 py-1 text-sm font-semibold text-indigo-700">
                        Tier {{ $progress['current_affiliate_tier'] }}
                    </span>
                @else
                    <span class="inline-flex items-center rounded-lg bg-gray-100 px-3 py-1 text-sm font-medium text-gray-500">
                        No tier yet
                    </span>
                @endif
            </div>

            @if($progress['at_max_affiliate_tier'])
                <div class="flex items-center gap-3 bg-green-50 border border-green-200 rounded-xl px-4 py-3">
                    <svg class="h-5 w-5 text-green-500 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <p class="text-sm font-medium text-green-700">
                        You are at the maximum affiliate tier ({{ number_format($progress['current_affiliate_rate'] * 100, 0) }}%)
                    </p>
                </div>
            @else
                <div class="space-y-4">
                    {{-- Context line --}}
                    <div class="text-xs text-gray-500">
                        Current: {{ $progress['current_affiliate_tier'] ? 'Tier ' . $progress['current_affiliate_tier'] . ' (' . number_format($progress['current_affiliate_rate'] * 100, 0) . '%)' : 'None' }}
                        @if($progress['next_affiliate_tier'])
                            &mdash; Next: Tier {{ $progress['next_affiliate_tier'] }} ({{ number_format($progress['next_affiliate_rate'] * 100, 0) }}%)
                        @endif
                    </div>

                    {{-- Customer progress bar --}}
                    <div>
                        <div class="flex justify-between text-xs text-gray-500 mb-1.5">
                            <span class="font-medium text-gray-700">Customers</span>
                            <span>{{ $progress['current_customers'] }} / {{ $progress['next_affiliate_min_customers'] ?? $progress['current_customers'] }}</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
                            <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 h-3 rounded-full transition-all duration-500" style="width: {{ $progress['customer_progress_percent'] }}%"></div>
                        </div>
                    </div>

                    {{-- Volume progress bar --}}
                    <div>
                        <div class="flex justify-between text-xs text-gray-500 mb-1.5">
                            <span class="font-medium text-gray-700">Volume</span>
                            <span>{{ number_format((float) $progress['current_volume'], 0) }} / {{ number_format($progress['next_affiliate_min_volume'] ?? (float) $progress['current_volume'], 0) }} XP</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
                            <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 h-3 rounded-full transition-all duration-500" style="width: {{ $progress['volume_progress_percent'] }}%"></div>
                        </div>
                    </div>

                    @if($progress['customers_needed'] > 0 || bccomp($progress['volume_needed'], '0', 4) > 0)
                    <p class="text-sm text-gray-600 bg-gray-50 rounded-lg px-3 py-2">
                        You need:
                        @if($progress['customers_needed'] > 0)
                            <span class="font-medium text-gray-800">{{ $progress['customers_needed'] }} more active customer{{ $progress['customers_needed'] > 1 ? 's' : '' }}</span>
                        @endif
                        @if($progress['customers_needed'] > 0 && bccomp($progress['volume_needed'], '0', 4) > 0) + @endif
                        @if(bccomp($progress['volume_needed'], '0', 4) > 0)
                            <span class="font-medium text-gray-800">{{ number_format((float) $progress['volume_needed'], 0) }} more XP</span>
                        @endif
                    </p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Viral Commission Section --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Qualifying Viral Volume</h3>
                @if($progress['current_viral_tier'])
                    <span class="inline-flex items-center rounded-lg bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-700">
                        Tier {{ $progress['current_viral_tier'] }}
                    </span>
                @else
                    <span class="inline-flex items-center rounded-lg bg-gray-100 px-3 py-1 text-sm font-medium text-gray-500">
                        No tier yet
                    </span>
                @endif
            </div>

            @if($progress['at_max_viral_tier'])
                <div class="flex items-center gap-3 bg-green-50 border border-green-200 rounded-xl px-4 py-3">
                    <svg class="h-5 w-5 text-green-500 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <p class="text-sm font-medium text-green-700">
                        You are at the maximum viral tier (Tier {{ $progress['current_viral_tier'] }})
                    </p>
                </div>
            @else
                <div class="space-y-4">
                    {{-- Context line --}}
                    <div class="text-xs text-gray-500">
                        Current: {{ $progress['current_viral_tier'] ? 'Tier ' . $progress['current_viral_tier'] . ' ($' . number_format($progress['current_viral_daily_reward'], 2) . '/day)' : 'None' }}
                        @if($progress['next_viral_tier'])
                            &mdash; Next: Tier {{ $progress['next_viral_tier'] }} (${{ number_format($progress['next_viral_daily_reward'], 2) }}/day)
                        @endif
                    </div>

                    {{-- QVV progress bar --}}
                    <div>
                        <div class="flex justify-between text-xs text-gray-500 mb-1.5">
                            <span class="font-medium text-gray-700">QVV</span>
                            <span>{{ number_format((float) $progress['current_qvv'], 0) }} / {{ number_format($progress['next_viral_min_qvv'] ?? (float) $progress['current_qvv'], 0) }} XP</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
                            <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 h-3 rounded-full transition-all duration-500" style="width: {{ $progress['qvv_progress_percent'] }}%"></div>
                        </div>
                        @if($progress['next_viral_min_qvv'])
                        <div class="flex justify-between text-xs text-gray-400 mt-1">
                            <span>0</span>
                            <span>{{ number_format($progress['next_viral_min_qvv'], 0) }} XP needed</span>
                        </div>
                        @endif
                    </div>

                    @if(bccomp($progress['qvv_needed'], '0', 4) > 0)
                    <p class="text-sm text-gray-600 bg-gray-50 rounded-lg px-3 py-2">
                        You need <span class="font-medium text-gray-800">{{ number_format((float) $progress['qvv_needed'], 0) }} more Qualifying Viral Volume XP</span>
                    </p>
                    @endif
                </div>
            @endif
        </div>

    </div>
    @else
        <div class="flex items-center justify-center py-8 text-gray-400">
            <svg class="h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
            </svg>
            <span class="text-sm">No active compensation plan found.</span>
        </div>
    @endif
</div>
