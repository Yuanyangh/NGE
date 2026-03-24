<div>
    {{-- Filters --}}
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="relative flex-1">
            <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
            <input
                wire:model.live.debounce.300ms="search"
                type="text"
                placeholder="Search by user name..."
                class="block w-full rounded-lg border border-slate-300 bg-white py-2 pl-10 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:placeholder:text-slate-500 dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
            >
        </div>
        <select
            wire:model.live="filterType"
            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
        >
            <option value="">All Types</option>
            <option value="affiliate_commission">Affiliate Commission</option>
            <option value="viral_commission">Viral Commission</option>
            <option value="cap_adjustment">Cap Adjustment</option>
        </select>
        <select
            wire:model.live="filterCompany"
            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
        >
            <option value="">All Companies</option>
            @foreach ($companies as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>
    </div>

    @if ($entries->isEmpty())
        <x-admin.empty-state
            icon="table-cells"
            heading="No ledger entries yet"
            description="Commission ledger entries will appear here after a commission run is executed."
        />
    @else
        <x-admin.data-table>
            <x-slot:header>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Company</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">User</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Run Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Type</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Amount</th>
                <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Tier</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Description</th>
            </x-slot:header>

            <x-slot:body>
                @foreach ($entries as $entry)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50">
                        <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-900 dark:text-white">
                            {{ $entry->company?->name ?? '-' }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                            {{ $entry->user?->name ?? '-' }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500 dark:text-slate-400">
                            {{ $entry->commissionRun?->run_date?->format('M j, Y') ?? '-' }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            @php
                                $typeColor = match($entry->type) {
                                    'affiliate_commission' => 'success',
                                    'viral_commission' => 'info',
                                    'cap_adjustment' => 'warning',
                                    default => 'gray',
                                };
                                $typeLabel = match($entry->type) {
                                    'affiliate_commission' => 'Affiliate',
                                    'viral_commission' => 'Viral',
                                    'cap_adjustment' => 'Cap Adj',
                                    default => $entry->type,
                                };
                            @endphp
                            <x-admin.badge :color="$typeColor">{{ $typeLabel }}</x-admin.badge>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <x-admin.money :amount="$entry->amount" :decimals="4" />
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-center text-sm text-slate-600 dark:text-slate-300">
                            {{ $entry->tier_achieved ?? '-' }}
                        </td>
                        <td class="max-w-[200px] truncate px-4 py-3 text-sm text-slate-500 dark:text-slate-400" title="{{ $entry->description }}">
                            {{ \Illuminate\Support\Str::limit($entry->description, 50) }}
                        </td>
                    </tr>
                @endforeach
            </x-slot:body>

            <x-slot:pagination>
                {{ $entries->links() }}
            </x-slot:pagination>
        </x-admin.data-table>
    @endif
</div>
