<div>
    @if ($movements->isEmpty())
        <x-admin.empty-state
            icon="arrows-right-left"
            heading="No movements yet"
            description="Wallet movements will appear here after commissions are credited."
        />
    @else
        <x-admin.data-table>
            <x-slot:header>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Type</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Amount</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Description</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Effective At</th>
            </x-slot:header>

            <x-slot:body>
                @foreach ($movements as $movement)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50">
                        <td class="whitespace-nowrap px-4 py-3">
                            @php
                                $typeColor = match($movement->type) {
                                    'credit' => 'success',
                                    'release' => 'info',
                                    'clawback' => 'danger',
                                    'withdrawal' => 'warning',
                                    default => 'gray',
                                };
                            @endphp
                            <x-admin.badge :color="$typeColor">{{ $movement->type }}</x-admin.badge>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <x-admin.money :amount="$movement->amount" :decimals="4" />
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            @php
                                $statusColor = match($movement->status) {
                                    'approved' => 'success',
                                    'pending' => 'warning',
                                    'reversed' => 'danger',
                                    'released' => 'info',
                                    default => 'gray',
                                };
                            @endphp
                            <x-admin.badge :color="$statusColor">{{ $movement->status }}</x-admin.badge>
                        </td>
                        <td class="max-w-[250px] truncate px-4 py-3 text-sm text-slate-500 dark:text-slate-400" title="{{ $movement->description }}">
                            {{ $movement->description ?? '-' }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500 dark:text-slate-400">
                            {{ $movement->effective_at?->format('M j, Y g:i A') }}
                        </td>
                    </tr>
                @endforeach
            </x-slot:body>

            <x-slot:pagination>
                {{ $movements->links() }}
            </x-slot:pagination>
        </x-admin.data-table>
    @endif
</div>
