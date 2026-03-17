<div class="bg-white overflow-hidden shadow rounded-lg">
    <div class="p-5">
        <h3 class="text-lg font-medium text-gray-900">Commission History</h3>

        {{-- Filters --}}
        <div class="mt-4 flex flex-wrap gap-4">
            <div>
                <label class="block text-xs text-gray-500">Type</label>
                <select wire:model.live="filterType" class="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All</option>
                    <option value="affiliate_commission">Affiliate Commission</option>
                    <option value="viral_commission">Viral Commission</option>
                    <option value="cap_adjustment">Cap Adjustment</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500">From</label>
                <input type="date" wire:model.live="filterDateFrom" class="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs text-gray-500">To</label>
                <input type="date" wire:model.live="filterDateTo" class="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
        </div>

        {{-- Table --}}
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tier</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($entries as $entry)
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $entry->created_at?->format('M j, Y') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ str_replace('_', ' ', ucfirst($entry->type)) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $entry->tier_achieved ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-right font-medium {{ (float) $entry->amount >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ (float) $entry->amount >= 0 ? '+' : '' }}${{ number_format((float) $entry->amount, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">No commission entries found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($entries instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="mt-4">
                {{ $entries->links() }}
            </div>
        @endif
    </div>
</div>
