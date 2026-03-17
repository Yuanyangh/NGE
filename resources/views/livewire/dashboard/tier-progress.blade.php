<div class="space-y-4">
    @if($progress)
    {{-- Affiliate Commission Progress --}}
    <div class="bg-white overflow-hidden shadow rounded-lg p-5">
        <h3 class="text-lg font-medium text-gray-900">Affiliate Commission Progress</h3>

        @if($progress['at_max_affiliate_tier'])
            <p class="mt-2 text-sm text-green-600 font-medium">You are at the maximum affiliate tier ({{ number_format($progress['current_affiliate_rate'] * 100, 0) }}%)</p>
        @else
            <div class="mt-3 space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">
                        Current: {{ $progress['current_affiliate_rate'] ? number_format($progress['current_affiliate_rate'] * 100, 0) . '%' : 'None' }}
                        ({{ $progress['current_customers'] }} customers, {{ number_format((float) $progress['current_volume'], 0) }} XP)
                    </span>
                    @if($progress['next_affiliate_rate'])
                    <span class="text-gray-600">
                        Next: {{ number_format($progress['next_affiliate_rate'] * 100, 0) }}%
                        (need {{ $progress['next_affiliate_min_customers'] }} customers + {{ number_format($progress['next_affiliate_min_volume'], 0) }} XP)
                    </span>
                    @endif
                </div>

                {{-- Customer progress bar --}}
                <div>
                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                        <span>Customers</span>
                        <span>{{ $progress['current_customers'] }}/{{ $progress['next_affiliate_min_customers'] ?? $progress['current_customers'] }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-indigo-600 h-2.5 rounded-full" style="width: {{ $progress['customer_progress_percent'] }}%"></div>
                    </div>
                </div>

                {{-- Volume progress bar --}}
                <div>
                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                        <span>Volume</span>
                        <span>{{ number_format((float) $progress['current_volume'], 0) }}/{{ number_format($progress['next_affiliate_min_volume'] ?? (float) $progress['current_volume'], 0) }} XP</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-indigo-600 h-2.5 rounded-full" style="width: {{ $progress['volume_progress_percent'] }}%"></div>
                    </div>
                </div>

                @if($progress['customers_needed'] > 0 || bccomp($progress['volume_needed'], '0', 4) > 0)
                <p class="text-sm text-gray-600">
                    You need:
                    @if($progress['customers_needed'] > 0)
                        {{ $progress['customers_needed'] }} more active customer{{ $progress['customers_needed'] > 1 ? 's' : '' }}
                    @endif
                    @if($progress['customers_needed'] > 0 && bccomp($progress['volume_needed'], '0', 4) > 0) + @endif
                    @if(bccomp($progress['volume_needed'], '0', 4) > 0)
                        {{ number_format((float) $progress['volume_needed'], 0) }} more XP
                    @endif
                </p>
                @endif
            </div>
        @endif
    </div>

    {{-- Viral Commission Progress --}}
    <div class="bg-white overflow-hidden shadow rounded-lg p-5">
        <h3 class="text-lg font-medium text-gray-900">Viral Commission Progress</h3>

        @if($progress['at_max_viral_tier'])
            <p class="mt-2 text-sm text-green-600 font-medium">You are at the maximum viral tier (Tier {{ $progress['current_viral_tier'] }})</p>
        @else
            <div class="mt-3 space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">
                        Current: {{ $progress['current_viral_tier'] ? 'Tier ' . $progress['current_viral_tier'] . ' ($' . number_format($progress['current_viral_daily_reward'], 2) . '/day)' : 'None' }}
                    </span>
                    @if($progress['next_viral_tier'])
                    <span class="text-gray-600">
                        Next: Tier {{ $progress['next_viral_tier'] }} (${{ number_format($progress['next_viral_daily_reward'], 2) }}/day) &mdash; need {{ number_format($progress['next_viral_min_qvv'], 0) }} QVV
                    </span>
                    @endif
                </div>

                {{-- QVV progress bar --}}
                <div>
                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                        <span>QVV</span>
                        <span>{{ number_format((float) $progress['current_qvv'], 0) }}/{{ number_format($progress['next_viral_min_qvv'] ?? (float) $progress['current_qvv'], 0) }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-emerald-500 h-2.5 rounded-full" style="width: {{ $progress['qvv_progress_percent'] }}%"></div>
                    </div>
                </div>

                @if(bccomp($progress['qvv_needed'], '0', 4) > 0)
                <p class="text-sm text-gray-600">You need {{ number_format((float) $progress['qvv_needed'], 0) }} more Qualifying Viral Volume</p>
                @endif
            </div>
        @endif
    </div>
    @else
        <div class="bg-white overflow-hidden shadow rounded-lg p-5">
            <p class="text-gray-500">No active compensation plan found.</p>
        </div>
    @endif
</div>
