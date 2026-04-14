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
        <div class="grid grid-cols-3 gap-4">@for ($i = 0; $i < 3; $i++) <div class="h-20 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800"></div> @endfor</div>
        <div class="h-64 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800"></div>
    </div>

    <div wire:loading.remove wire:target="regenerate" class="space-y-6">

        {{-- Summary --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-admin.stat-card label="Total Runs" :value="number_format($this->summary['total_runs'])" color="indigo" />
            <x-admin.stat-card label="Viral Cap Triggered" :value="number_format($this->summary['viral_triggers']) . ' run(s)'" color="orange" />
            <x-admin.stat-card label="Total Cap Reduction" :value="'$' . number_format((float) $this->summary['cap_reduction'], 2)" color="rose" />
        </div>

        {{-- Viral cap warning --}}
        @if ($this->summary['viral_triggers'] > 0)
            <div class="flex items-start gap-3 rounded-xl border border-orange-300 bg-orange-50 px-5 py-4 dark:border-orange-700 dark:bg-orange-900/20">
                <svg class="mt-0.5 size-5 shrink-0 text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                </svg>
                <p class="text-sm text-orange-800 dark:text-orange-300">
                    The viral QVV cap was triggered {{ $this->summary['viral_triggers'] }} time(s). Affiliates in those runs had viral commission reduced.
                </p>
            </div>
        @endif

        {{-- Run details --}}
        <x-admin.form-section title="Run Details" description="Per-run breakdown showing viral cap status.">
            @if ($this->runs->isEmpty())
                <x-admin.empty-state message="No commission runs found in this period." />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th class="pb-3 pr-4 text-xs font-medium uppercase tracking-wider text-slate-500">Run Date</th>
                                <th class="pb-3 pr-4 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Affiliate Comm.</th>
                                <th class="pb-3 pr-4 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Viral Comm.</th>
                                <th class="pb-3 text-xs font-medium uppercase tracking-wider text-slate-500">Viral Cap</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($this->runs as $i => $run)
                                <tr class="{{ $i % 2 === 0 ? '' : 'bg-slate-50/50 dark:bg-slate-800/30' }}">
                                    <td class="py-3 pr-4 font-medium text-slate-900 dark:text-white">{{ $run['run_date'] }}</td>
                                    <td class="py-3 pr-4 text-right tabular-nums text-emerald-700 dark:text-emerald-400">${{ number_format((float) $run['aff_comm'], 2) }}</td>
                                    <td class="py-3 pr-4 text-right tabular-nums text-violet-700 dark:text-violet-400">${{ number_format((float) $run['viral_comm'], 2) }}</td>
                                    <td class="py-3">
                                        @if ($run['viral_cap_triggered'])
                                            <span class="rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-700 dark:bg-orange-900/30 dark:text-orange-400">Triggered</span>
                                        @else
                                            <span class="text-xs text-slate-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-admin.form-section>

        {{-- Top cap-affected affiliates --}}
        @if ($this->capAdjustments->isNotEmpty())
            <x-admin.form-section title="Most Affected Affiliates" description="Affiliates with the largest total cap reductions.">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th class="pb-3 pr-4 text-xs font-medium uppercase tracking-wider text-slate-500">Affiliate</th>
                                <th class="pb-3 pr-4 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Total Reduction</th>
                                <th class="pb-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Adjustments</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($this->capAdjustments as $i => $row)
                                <tr class="{{ $i % 2 === 0 ? '' : 'bg-slate-50/50 dark:bg-slate-800/30' }}">
                                    <td class="py-3 pr-4 font-medium text-slate-900 dark:text-white">{{ $row['name'] }}</td>
                                    <td class="py-3 pr-4 text-right tabular-nums font-semibold text-rose-700 dark:text-rose-400">${{ number_format((float) $row['total_reduction'], 2) }}</td>
                                    <td class="py-3 text-right tabular-nums text-slate-700 dark:text-slate-300">{{ $row['count'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-admin.form-section>
        @endif

    </div>
</div>
