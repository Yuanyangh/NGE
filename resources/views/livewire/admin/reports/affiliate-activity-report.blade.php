<style>
    @media print {
        aside, header, .no-print { display: none !important; }
        main { padding: 0 !important; }
        .lg\:pl-64 { padding-left: 0 !important; }
        body { background: #fff !important; }
    }
</style>

<div class="space-y-6">
    @include('livewire.admin.reports.partials.date-filter')

    <div wire:loading wire:target="regenerate" class="space-y-4">
        <div class="grid grid-cols-4 gap-4">@for ($i = 0; $i < 4; $i++) <div class="h-20 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800"></div> @endfor</div>
        <div class="h-64 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800"></div>
    </div>

    <div wire:loading.remove wire:target="regenerate" class="space-y-6">

        {{-- Summary --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <x-admin.stat-card label="Total Affiliates" :value="number_format($this->summary['total'])" color="indigo" />
            <x-admin.stat-card label="Ordered This Period" :value="number_format($this->summary['active'])" color="emerald" />
            <x-admin.stat-card label="Earned Commissions" :value="number_format($this->summary['earning'])" color="amber" />
            <x-admin.stat-card label="No Orders" :value="number_format($this->summary['passive'])" color="rose" />
        </div>

        {{-- Table --}}
        <x-admin.form-section title="Affiliate Activity Detail" description="Sorted by earnings descending.">
            @if ($this->affiliates->isEmpty())
                <x-admin.empty-state message="No affiliates found for this company." />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th class="pb-3 pr-4 text-xs font-medium uppercase tracking-wider text-slate-500">Affiliate</th>
                                <th class="pb-3 pr-4 text-xs font-medium uppercase tracking-wider text-slate-500">Status</th>
                                <th class="pb-3 pr-4 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Orders</th>
                                <th class="pb-3 pr-4 text-right text-xs font-medium uppercase tracking-wider text-slate-500">XP</th>
                                <th class="pb-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Earned</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($this->affiliates as $i => $affiliate)
                                <tr class="{{ $i % 2 === 0 ? '' : 'bg-slate-50/50 dark:bg-slate-800/30' }} hover:bg-indigo-50/40 dark:hover:bg-indigo-900/10">
                                    <td class="py-3 pr-4 font-medium text-slate-900 dark:text-white">{{ $affiliate['name'] }}</td>
                                    <td class="py-3 pr-4">
                                        <span class="rounded-full px-2.5 py-0.5 text-xs font-medium
                                            {{ $affiliate['status'] === 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400' }}">
                                            {{ ucfirst($affiliate['status']) }}
                                        </span>
                                    </td>
                                    <td class="py-3 pr-4 text-right tabular-nums text-slate-700 dark:text-slate-300">{{ $affiliate['order_count'] }}</td>
                                    <td class="py-3 pr-4 text-right tabular-nums text-slate-700 dark:text-slate-300">{{ number_format((float) $affiliate['total_xp'], 0) }}</td>
                                    <td class="py-3 text-right tabular-nums font-semibold {{ bccomp($affiliate['earned'], '0', 2) > 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-400' }}">
                                        ${{ number_format((float) $affiliate['earned'], 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-admin.form-section>

    </div>
</div>
