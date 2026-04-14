<style>
    @media print {
        aside, header, .no-print { display: none !important; }
        main { padding: 0 !important; }
        .lg\:pl-64 { padding-left: 0 !important; }
        body { background: #fff !important; }
        .print-bar { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
</style>

<div class="space-y-6">
    @include('livewire.admin.reports.partials.date-filter')

    <div wire:loading wire:target="regenerate" class="h-64 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800"></div>

    <div wire:loading.remove wire:target="regenerate" class="space-y-6">

        <x-admin.stat-card label="Total Bonuses Paid" :value="'$' . number_format((float) $this->totalBonuses, 2)" color="rose" />

        <x-admin.form-section title="Bonus Breakdown by Type" description="Aggregated by bonus type for the selected period.">
            @if ($this->byBonusType->isEmpty())
                <x-admin.empty-state message="No bonus payouts found in this period." />
            @else
                @php
                    $maxAmount = $this->byBonusType->max(fn ($r) => (float) $r['total_amount']) ?: 1;
                    $typeColors = [
                        'matching'         => 'bg-indigo-400 dark:bg-indigo-500',
                        'fast_start'       => 'bg-emerald-400 dark:bg-emerald-500',
                        'pool_sharing'     => 'bg-amber-400 dark:bg-amber-500',
                        'rank_advancement' => 'bg-violet-400 dark:bg-violet-500',
                        'leadership'       => 'bg-rose-400 dark:bg-rose-500',
                    ];
                @endphp
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th class="pb-3 pr-4 text-xs font-medium uppercase tracking-wider text-slate-500">Bonus Type</th>
                                <th class="pb-3 pr-4 text-xs font-medium uppercase tracking-wider text-slate-500">Category</th>
                                <th class="pb-3 pr-4 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Recipients</th>
                                <th class="pb-3 pr-4 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Payouts</th>
                                <th class="pb-3 pr-4 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Total</th>
                                <th class="pb-3 text-xs font-medium uppercase tracking-wider text-slate-500">Share</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($this->byBonusType as $i => $row)
                                @php
                                    $barPct = (int) round(((float) $row['total_amount'] / $maxAmount) * 100);
                                    $barColor = $typeColors[$row['type']] ?? 'bg-slate-400';
                                @endphp
                                <tr class="{{ $i % 2 === 0 ? '' : 'bg-slate-50/50 dark:bg-slate-800/30' }} hover:bg-amber-50/40 dark:hover:bg-amber-900/10">
                                    <td class="py-3 pr-4 font-medium text-slate-900 dark:text-white">{{ $row['name'] }}</td>
                                    <td class="py-3 pr-4 text-slate-500 dark:text-slate-400">{{ str_replace('_', ' ', $row['type']) }}</td>
                                    <td class="py-3 pr-4 text-right tabular-nums text-slate-700 dark:text-slate-300">{{ $row['recipient_count'] }}</td>
                                    <td class="py-3 pr-4 text-right tabular-nums text-slate-700 dark:text-slate-300">{{ $row['payout_count'] }}</td>
                                    <td class="py-3 pr-4 text-right tabular-nums font-semibold text-slate-900 dark:text-white">${{ number_format((float) $row['total_amount'], 2) }}</td>
                                    <td class="py-3 min-w-[8rem]">
                                        <div class="h-3 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                            <div class="print-bar h-3 rounded-full {{ $barColor }}" style="width: {{ $barPct }}%;"></div>
                                        </div>
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
