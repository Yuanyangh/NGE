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
            <x-admin.stat-card label="Total Movements" :value="number_format($this->summary['count'])" color="indigo" />
            <x-admin.stat-card label="Credits + Releases" :value="'$' . number_format((float) $this->summary['credits'], 2)" color="emerald" />
            <x-admin.stat-card label="Clawbacks" :value="'$' . number_format((float) $this->summary['clawbacks'], 2)" color="rose" />
            <x-admin.stat-card label="Withdrawals" :value="'$' . number_format((float) $this->summary['withdrawals'], 2)" color="amber" />
        </div>

        {{-- Movements table --}}
        <x-admin.form-section title="Movement Ledger" description="Last 200 wallet movements in the selected period.">
            @if ($this->movements->isEmpty())
                <x-admin.empty-state message="No wallet movements in this period." />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th class="pb-3 pr-4 text-xs font-medium uppercase tracking-wider text-slate-500">Affiliate</th>
                                <th class="pb-3 pr-4 text-xs font-medium uppercase tracking-wider text-slate-500">Type</th>
                                <th class="pb-3 pr-4 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Amount</th>
                                <th class="pb-3 pr-4 text-xs font-medium uppercase tracking-wider text-slate-500">Status</th>
                                <th class="pb-3 text-xs font-medium uppercase tracking-wider text-slate-500">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($this->movements as $i => $movement)
                                @php
                                    $typeBadge = match($movement['type']) {
                                        'credit'     => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                                        'release'    => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400',
                                        'clawback'   => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
                                        'withdrawal' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                        default      => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400',
                                    };
                                    $isPositive = in_array($movement['type'], ['credit', 'release'], true);
                                    $amountColor = $isPositive ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400';
                                    $statusBadge = match($movement['status']) {
                                        'completed', 'approved', 'released' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                                        'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                        'reversed', 'failed' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
                                        default => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400',
                                    };
                                @endphp
                                <tr class="{{ $i % 2 === 0 ? '' : 'bg-slate-50/50 dark:bg-slate-800/30' }} hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10">
                                    <td class="py-3 pr-4 font-medium text-slate-900 dark:text-white">{{ $movement['name'] }}</td>
                                    <td class="py-3 pr-4">
                                        <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $typeBadge }}">{{ $movement['type'] }}</span>
                                    </td>
                                    <td class="py-3 pr-4 text-right tabular-nums font-semibold {{ $amountColor }}">
                                        {{ $isPositive ? '+' : '' }}${{ number_format(abs((float) $movement['amount']), 2) }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusBadge }}">{{ $movement['status'] }}</span>
                                    </td>
                                    <td class="py-3 text-slate-600 dark:text-slate-400">
                                        {{ \Carbon\Carbon::parse($movement['created_at'])->format('M j, Y g:i A') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($this->movements->count() >= 200)
                    <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">Showing first 200 movements. Narrow the date range to see more granular data.</p>
                @endif
            @endif
        </x-admin.form-section>

    </div>
</div>
