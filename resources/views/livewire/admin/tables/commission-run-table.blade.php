<div>
    {{-- Filters --}}
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="relative flex-1">
            <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
            <input
                wire:model.live.debounce.300ms="search"
                type="text"
                placeholder="Search by company..."
                class="block w-full rounded-lg border border-slate-300 bg-white py-2 pl-10 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:placeholder:text-slate-500 dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
            >
        </div>
        <select
            wire:model.live="filterStatus"
            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
        >
            <option value="">All Statuses</option>
            <option value="completed">Completed</option>
            <option value="running">Running</option>
            <option value="failed">Failed</option>
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
        <button
            @click="$dispatch('open-modal-trigger-run')"
            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900"
        >
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM15.91 11.672a.375.375 0 0 1 0 .656l-5.603 3.113a.375.375 0 0 1-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112Z"/></svg>
            Trigger Run
        </button>
    </div>

    {{-- Trigger Run Modal --}}
    <x-admin.modal name="trigger-run" title="Trigger Commission Run">
        <form id="trigger-run-form" method="POST" action="{{ route('admin.commission-runs.trigger') }}">
            @csrf
            <div class="space-y-4">
                <div>
                    <label for="trigger-company" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Company</label>
                    <select
                        id="trigger-company"
                        name="company_id"
                        required
                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                    >
                        <option value="">Select a company...</option>
                        @foreach ($companies as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="trigger-date" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Run Date</label>
                    <input
                        id="trigger-date"
                        type="date"
                        name="date"
                        required
                        value="{{ now()->toDateString() }}"
                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                    >
                </div>
            </div>
        <x-slot:footer>
            <button type="button" @click="$dispatch('close-modal-trigger-run')" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                Cancel
            </button>
            <button type="submit" form="trigger-run-form" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-500">
                Run Commissions
            </button>
        </x-slot:footer>
        </form>
    </x-admin.modal>

    @if ($runs->isEmpty())
        <x-admin.empty-state
            icon="play-circle"
            heading="No commission runs found"
            description="Commission runs will appear here after they are triggered."
        />
    @else
        <x-admin.data-table>
            <x-slot:header>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Company</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Plan</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    <button wire:click="sortBy('run_date')" class="group inline-flex items-center gap-1">
                        Run Date
                        @if ($sortField === 'run_date')
                            <svg class="size-3.5 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 17a.75.75 0 0 1-.75-.75V5.612L5.29 9.77a.75.75 0 0 1-1.08-1.04l5.25-5.5a.75.75 0 0 1 1.08 0l5.25 5.5a.75.75 0 1 1-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0 1 10 17Z" clip-rule="evenodd"/></svg>
                        @else
                            <svg class="size-3.5 text-slate-300 dark:text-slate-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .55.24l3.25 3.5a.75.75 0 1 1-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 0 1-1.1-1.02l3.25-3.5A.75.75 0 0 1 10 3Zm-3.76 9.2a.75.75 0 0 1 1.06.04l2.7 2.908 2.7-2.908a.75.75 0 1 1 1.1 1.02l-3.25 3.5a.75.75 0 0 1-1.1 0l-3.25-3.5a.75.75 0 0 1 .04-1.06Z" clip-rule="evenodd"/></svg>
                        @endif
                    </button>
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Status</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Affiliate $</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Viral $</th>
                <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Cap</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Actions</th>
            </x-slot:header>

            <x-slot:body>
                @foreach ($runs as $run)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50">
                        <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-900 dark:text-white">
                            {{ $run->company?->name ?? '-' }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500 dark:text-slate-400">
                            {{ $run->compensationPlan?->name ?? '-' }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                            {{ $run->run_date?->format('M j, Y') }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            @php
                                $statusColor = match($run->status) {
                                    'completed' => 'success',
                                    'running' => 'warning',
                                    'failed' => 'danger',
                                    default => 'gray',
                                };
                            @endphp
                            <x-admin.badge :color="$statusColor">{{ $run->status }}</x-admin.badge>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <x-admin.money :amount="$run->total_affiliate_commission" />
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <x-admin.money :amount="$run->total_viral_commission" />
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-center">
                            @if ($run->viral_cap_triggered)
                                <svg class="mx-auto size-5 text-amber-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
                            @else
                                <span class="text-slate-300 dark:text-slate-600">-</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                            <a href="{{ route('admin.commission-runs.show', $run->id) }}" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">View</a>
                        </td>
                    </tr>
                @endforeach
            </x-slot:body>

            <x-slot:pagination>
                {{ $runs->links() }}
            </x-slot:pagination>
        </x-admin.data-table>
    @endif
</div>
