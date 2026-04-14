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

    <div wire:loading wire:target="regenerate" class="space-y-4">
        <div class="grid grid-cols-3 gap-4">@for ($i = 0; $i < 3; $i++) <div class="h-20 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800"></div> @endfor</div>
        <div class="h-48 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800"></div>
    </div>

    <div wire:loading.remove wire:target="regenerate" class="space-y-6">

        {{-- Summary cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-admin.stat-card label="Transactions" :value="number_format($this->summary['tx_count'])" color="indigo" />
            <x-admin.stat-card label="Total XP" :value="number_format((float) $this->summary['total_xp'], 0)" color="emerald" />
            <x-admin.stat-card label="Qualifying XP" :value="number_format((float) $this->summary['qualifying_xp'], 0)" color="amber" />
        </div>

        {{-- Daily volume chart --}}
        <x-admin.form-section title="Daily Volume Trend" description="Total XP per day across all transactions.">
            @if ($this->dailyVolume->isEmpty())
                <x-admin.empty-state message="No transaction data for this period." />
            @else
                @php $maxXp = $this->dailyVolume->max(fn ($p) => (float) $p['xp']) ?: 1; @endphp
                <div class="flex h-48 items-end gap-0.5 overflow-x-auto">
                    @foreach ($this->dailyVolume as $point)
                        @php $pct = max(1, (int) round(((float) $point['xp'] / $maxXp) * 100)); @endphp
                        <div class="group relative flex-1 min-w-[6px]" title="{{ $point['date'] }}: {{ number_format((float) $point['xp'], 0) }} XP">
                            <div class="print-bar w-full rounded-t bg-emerald-400 transition-colors group-hover:bg-emerald-600 dark:bg-emerald-500" style="height: {{ $pct }}%;"></div>
                        </div>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ $this->dailyVolume->count() }} days. Hover bars for details.</p>
            @endif
        </x-admin.form-section>

        {{-- Top 10 by volume --}}
        <x-admin.form-section title="Top 10 by Personal Volume" description="Affiliates with the highest qualifying XP in the period.">
            @if ($this->topVolume->isEmpty())
                <x-admin.empty-state message="No qualifying volume found." />
            @else
                @php $maxVol = $this->topVolume->max(fn ($r) => (float) $r['total_xp']) ?: 1; @endphp
                <div class="space-y-3">
                    @foreach ($this->topVolume as $i => $row)
                        @php $barPct = (int) round(((float) $row['total_xp'] / $maxVol) * 100); @endphp
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="font-medium text-slate-900 dark:text-white">{{ $row['name'] }}</span>
                                <span class="tabular-nums text-slate-700 dark:text-slate-300">{{ number_format((float) $row['total_xp'], 0) }} XP</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                <div class="print-bar h-2 rounded-full bg-emerald-400" style="width: {{ $barPct }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-admin.form-section>

    </div>
</div>
