<div class="bg-white rounded-xl shadow-sm">
    {{-- Header --}}
    <div class="p-6 border-b border-gray-100">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                </svg>
                <h3 class="text-lg font-semibold text-gray-900">Commission History</h3>
            </div>

            {{-- Date filters --}}
            <div class="flex gap-3 items-end">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                    <input type="date" wire:model.live="filterDateFrom" class="rounded-lg border-gray-200 text-sm py-2 px-3 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                    <input type="date" wire:model.live="filterDateTo" class="rounded-lg border-gray-200 text-sm py-2 px-3 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="mt-4 flex gap-1 border-b border-gray-100 -mb-px">
            <button
                wire:click="setTab('overview')"
                class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors duration-150 cursor-pointer
                    {{ $tab === 'overview'
                        ? 'border-indigo-500 text-indigo-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
            >Overview</button>
            <button
                wire:click="setTab('affiliate')"
                class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors duration-150 cursor-pointer
                    {{ $tab === 'affiliate'
                        ? 'border-indigo-500 text-indigo-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
            >Affiliate</button>
            <button
                wire:click="setTab('viral')"
                class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors duration-150 cursor-pointer
                    {{ $tab === 'viral'
                        ? 'border-indigo-500 text-indigo-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
            >Viral</button>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tier</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($entries as $entry)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $entry->created_at?->format('M j, Y') }}</td>
                    <td class="px-6 py-4 text-sm">
                        @php
                            $typeBadge = match($entry->type) {
                                'affiliate_commission' => 'bg-blue-100 text-blue-700',
                                'viral_commission'     => 'bg-emerald-100 text-emerald-700',
                                default                => 'bg-gray-100 text-gray-600',
                            };
                        @endphp
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $typeBadge }}">
                            {{ str_replace('_', ' ', ucfirst($entry->type)) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        @if($entry->tier_achieved)
                            @if($entry->type === 'affiliate_commission')
                                {{ $affiliateRateMap[$entry->tier_achieved] ?? 'Tier ' . $entry->tier_achieved }}
                            @elseif($entry->type === 'viral_commission')
                                {{ $viralRewardMap[$entry->tier_achieved] ?? 'Tier ' . $entry->tier_achieved }}
                            @else
                                {{ $entry->tier_achieved }}
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-right font-semibold {{ (float) $entry->amount >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ (float) $entry->amount >= 0 ? '+' : '' }}${{ number_format((float) $entry->amount, 2) }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-12 text-center">
                        <svg class="mx-auto h-8 w-8 text-gray-300 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                        </svg>
                        <p class="text-sm text-gray-500">No commission entries found.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($entries instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="p-4 border-t border-gray-100">
            {{ $entries->links() }}
        </div>
    @endif
</div>
