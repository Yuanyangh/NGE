<style>
    @media print {
        aside, header, .no-print { display: none !important; }
        main { padding: 0 !important; }
        .lg\:pl-64 { padding-left: 0 !important; }
        body { background: #fff !important; }
    }
</style>

<div class="space-y-6">
    {{-- Note: churn uses today's snapshot, not a date range. Show an info callout. --}}
    <div class="rounded-xl border border-sky-200 bg-sky-50 px-5 py-4 text-sm text-sky-800 dark:border-sky-700 dark:bg-sky-900/20 dark:text-sky-300">
        <strong>Snapshot as of today ({{ now()->format('M j, Y') }}).</strong>
        Churn risk is calculated from live affiliate activity data — it reflects the current state, not a historical period.
    </div>

    <div wire:loading class="space-y-4">
        <div class="grid grid-cols-4 gap-4">@for ($i = 0; $i < 4; $i++) <div class="h-20 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800"></div> @endfor</div>
        <div class="h-64 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800"></div>
    </div>

    <div wire:loading.remove class="space-y-6">

        {{-- Summary cards --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-5 dark:border-rose-800/40 dark:bg-rose-900/10">
                <p class="text-2xl font-bold tabular-nums text-rose-700 dark:text-rose-400">{{ $this->summary['inactive_warning'] }}</p>
                <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">Inactive warning</p>
            </div>
            <div class="rounded-xl border border-orange-200 bg-orange-50 p-5 dark:border-orange-800/40 dark:bg-orange-900/10">
                <p class="text-2xl font-bold tabular-nums text-orange-700 dark:text-orange-400">{{ $this->summary['at_risk'] }}</p>
                <p class="mt-1 text-sm text-orange-600 dark:text-orange-400">At risk</p>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-800/40 dark:bg-amber-900/10">
                <p class="text-2xl font-bold tabular-nums text-amber-700 dark:text-amber-400">{{ $this->summary['declining'] }}</p>
                <p class="mt-1 text-sm text-amber-600 dark:text-amber-400">Declining</p>
            </div>
            <div class="rounded-xl border border-blue-200 bg-blue-50 p-5 dark:border-blue-800/40 dark:bg-blue-900/10">
                <p class="text-2xl font-bold tabular-nums text-blue-700 dark:text-blue-400">{{ $this->summary['stagnant_leader'] }}</p>
                <p class="mt-1 text-sm text-blue-600 dark:text-blue-400">Stagnant leader</p>
            </div>
        </div>

        {{-- Affiliate table --}}
        <x-admin.form-section title="At-Risk Affiliates" :description="'Total flagged: ' . $this->summary['total']">
            @if ($this->results->isEmpty())
                <div class="flex flex-col items-center gap-3 py-10 text-center">
                    <svg class="size-12 text-emerald-400 dark:text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300">All clear — no affiliates flagged at risk.</p>
                </div>
            @else
                @php
                    $sortOrder = ['inactive_warning' => 0, 'at_risk' => 1, 'declining' => 2, 'stagnant_leader' => 3];
                    $sorted = $this->results->sortBy(fn ($r) => $sortOrder[$r->risk_level] ?? 99)->values();
                    $riskBadges = [
                        'inactive_warning' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-400',
                        'at_risk'          => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-400',
                        'declining'        => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400',
                        'stagnant_leader'  => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',
                    ];
                @endphp
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th class="pb-3 pr-4 text-xs font-medium uppercase tracking-wider text-slate-500">Affiliate</th>
                                <th class="pb-3 pr-4 text-xs font-medium uppercase tracking-wider text-slate-500">Risk Level</th>
                                <th class="pb-3 pr-4 text-xs font-medium uppercase tracking-wider text-slate-500">Reason</th>
                                <th class="pb-3 pr-4 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Days Since Order</th>
                                <th class="pb-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Vol. Change</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($sorted as $i => $result)
                                <tr class="{{ $i % 2 === 0 ? '' : 'bg-slate-50/50 dark:bg-slate-800/30' }}">
                                    <td class="py-3 pr-4 font-medium text-slate-900 dark:text-white">{{ $result->user_name }}</td>
                                    <td class="py-3 pr-4">
                                        <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $riskBadges[$result->risk_level] ?? '' }}">
                                            {{ str_replace('_', ' ', $result->risk_level) }}
                                        </span>
                                    </td>
                                    <td class="py-3 pr-4 max-w-xs text-xs text-slate-600 dark:text-slate-400">{{ $result->reason }}</td>
                                    <td class="py-3 pr-4 text-right tabular-nums text-slate-700 dark:text-slate-300">
                                        {{ $result->days_since_last_order !== null ? $result->days_since_last_order . 'd' : '—' }}
                                    </td>
                                    <td class="py-3 text-right tabular-nums">
                                        @if ($result->volume_change_pct !== null)
                                            <span class="{{ str_starts_with($result->volume_change_pct, '-') ? 'text-rose-700 dark:text-rose-400' : 'text-emerald-700 dark:text-emerald-400' }}">
                                                {{ $result->volume_change_pct }}%
                                            </span>
                                        @else
                                            <span class="text-slate-400">—</span>
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
