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

    {{-- Loading skeleton --}}
    <div wire:loading wire:target="regenerate" class="space-y-4">
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-6">
            @for ($i = 0; $i < 6; $i++) <div class="h-20 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800"></div> @endfor
        </div>
        <div class="h-64 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800"></div>
    </div>

    <div wire:loading.remove wire:target="regenerate" class="space-y-6">

        {{-- Totals summary --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-3">
            <x-admin.stat-card label="Commission Runs" :value="number_format($this->totals['run_count'])" color="indigo" />
            <x-admin.stat-card label="Total Volume (XP)" :value="number_format((float) $this->totals['volume'], 0)" color="sky" />
            <x-admin.stat-card label="Affiliate Commissions" :value="'$' . number_format((float) $this->totals['aff_comm'], 2)" color="emerald" />
            <x-admin.stat-card label="Viral Commissions" :value="'$' . number_format((float) $this->totals['viral_comm'], 2)" color="violet" />
            <x-admin.stat-card label="Total Bonuses" :value="'$' . number_format((float) $this->totals['bonuses'], 2)" color="amber" />
            <x-admin.stat-card label="Total Payout" :value="'$' . number_format((float) $this->totals['total_payout'], 2)" color="rose" />
        </div>

        {{-- Runs table --}}
        <x-admin.form-section title="Run Breakdown" description="One row per completed commission run in the selected period.">
            @if ($this->runs->isEmpty())
                <x-admin.empty-state message="No completed commission runs in this period." />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th class="pb-3 pr-4 text-xs font-medium uppercase tracking-wider text-slate-500">Run Date</th>
                                <th class="pb-3 pr-4 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Volume (XP)</th>
                                <th class="pb-3 pr-4 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Affiliate</th>
                                <th class="pb-3 pr-4 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Viral</th>
                                <th class="pb-3 pr-4 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Bonuses</th>
                                <th class="pb-3 pr-4 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Total Payout</th>
                                <th class="pb-3 text-xs font-medium uppercase tracking-wider text-slate-500">Cap</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($this->runs as $run)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                    <td class="py-3 pr-4 font-medium text-slate-900 dark:text-white">{{ $run['run_date'] }}</td>
                                    <td class="py-3 pr-4 text-right tabular-nums text-slate-700 dark:text-slate-300">{{ number_format((float) $run['total_volume'], 0) }}</td>
                                    <td class="py-3 pr-4 text-right tabular-nums text-emerald-700 dark:text-emerald-400">${{ number_format((float) $run['affiliate_commission'], 2) }}</td>
                                    <td class="py-3 pr-4 text-right tabular-nums text-violet-700 dark:text-violet-400">${{ number_format((float) $run['viral_commission'], 2) }}</td>
                                    <td class="py-3 pr-4 text-right tabular-nums text-amber-700 dark:text-amber-400">${{ number_format((float) $run['bonuses'], 2) }}</td>
                                    <td class="py-3 pr-4 text-right tabular-nums font-semibold text-slate-900 dark:text-white">${{ number_format((float) $run['total_payout'], 2) }}</td>
                                    <td class="py-3">
                                        @if ($run['viral_cap_triggered'])
                                            <span class="rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700 dark:bg-orange-900/30 dark:text-orange-400">Capped</span>
                                        @endif
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
