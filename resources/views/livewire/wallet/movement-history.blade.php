<div class="bg-white overflow-hidden shadow rounded-lg">
    <div class="p-5">
        <h3 class="text-lg font-medium text-gray-900">Recent Movements</h3>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($movements as $movement)
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $movement->effective_at?->format('M j, Y') ?? $movement->created_at?->format('M j, Y') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ str_replace('_', ' ', ucfirst($movement->type)) }}</td>
                        <td class="px-4 py-3 text-sm text-right font-medium {{ (float) $movement->amount >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ (float) $movement->amount >= 0 ? '+' : '' }}${{ number_format(abs((float) $movement->amount), 2) }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @php
                                $statusColor = match($movement->status) {
                                    'approved', 'released' => 'bg-green-100 text-green-800',
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'reversed' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800',
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColor }}">
                                {{ ucfirst($movement->status) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">No wallet movements yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($movements instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="mt-4">
                {{ $movements->links() }}
            </div>
        @endif
    </div>
</div>
