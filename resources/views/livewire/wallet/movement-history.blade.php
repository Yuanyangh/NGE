<div class="bg-white rounded-xl shadow-sm">
    {{-- Header --}}
    <div class="p-6 border-b border-gray-100">
        <div class="flex items-center gap-2">
            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-900">Transaction History</h3>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($movements as $movement)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $movement->effective_at?->format('M j, Y') ?? $movement->created_at?->format('M j, Y') }}</td>
                    <td class="px-6 py-4 text-sm">
                        @php
                            $typeBadge = match($movement->type) {
                                'credit'     => 'bg-green-100 text-green-700',
                                'release'    => 'bg-blue-100 text-blue-700',
                                'clawback'   => 'bg-red-100 text-red-700',
                                'withdrawal' => 'bg-amber-100 text-amber-700',
                                default      => 'bg-gray-100 text-gray-600',
                            };
                        @endphp
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $typeBadge }}">
                            {{ str_replace('_', ' ', ucfirst($movement->type)) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-right font-semibold {{ (float) $movement->amount >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ (float) $movement->amount >= 0 ? '+' : '' }}${{ number_format(abs((float) $movement->amount), 2) }}
                    </td>
                    <td class="px-6 py-4 text-sm">
                        @php
                            $statusBadge = match($movement->status) {
                                'approved', 'released', 'completed' => 'bg-green-100 text-green-700',
                                'pending'                           => 'bg-amber-100 text-amber-700',
                                'reversed', 'failed'               => 'bg-red-100 text-red-700',
                                default                             => 'bg-gray-100 text-gray-600',
                            };
                        @endphp
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusBadge }}">
                            {{ ucfirst($movement->status) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-12 text-center">
                        <svg class="mx-auto h-8 w-8 text-gray-300 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3" />
                        </svg>
                        <p class="text-sm text-gray-500">No wallet movements yet.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($movements instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="p-4 border-t border-gray-100">
            {{ $movements->links() }}
        </div>
    @endif
</div>
