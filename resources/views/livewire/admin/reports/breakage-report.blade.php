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
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">@for ($i = 0; $i < 4; $i++) <div class="h-20 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800"></div> @endfor</div>
    </div>

    <div wire:loading.remove wire:target="regenerate" class="space-y-6">

        {{-- Breakage rate highlight --}}
        @php
            $rate = (float) $this->data->breakageRate;
            $rateColor = $rate >= 20 ? 'border-rose-300 bg-rose-50 text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-400'
                : ($rate >= 10 ? 'border-orange-300 bg-orange-50 text-orange-700 dark:border-orange-700 dark:bg-orange-900/20 dark:text-orange-400'
                : 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400');
        @endphp
        <div class="rounded-xl border p-5 {{ $rateColor }}">
            <div class="flex items-baseline gap-3">
                <span class="text-4xl font-bold tabular-nums">{{ $this->data->breakageRate }}%</span>
                <span class="text-sm font-medium">overall breakage rate</span>
            </div>
            <p class="mt-1 text-sm opacity-80">
                {{ $rate < 10 ? 'Healthy — most commissions are being paid out.' : ($rate < 20 ? 'Moderate — review cap and volume settings.' : 'High — significant commission not being paid out.') }}
            </p>
        </div>

        {{-- Four metric cards --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <x-admin.stat-card label="Wasted Volume (XP)" :value="number_format((float) $this->data->wastedVolumeXp, 0) . ' XP'" color="rose" />
            <x-admin.stat-card label="Wasted %" :value="$this->data->wastedPercentage . '%'" color="orange" />
            <x-admin.stat-card label="Cap Reduction" :value="'$' . number_format((float) $this->data->totalCapReduction, 2)" color="amber" />
            <x-admin.stat-card label="Clawbacks" :value="'$' . number_format((float) $this->data->clawbackTotal, 2)" color="indigo" />
        </div>

        {{-- Detailed breakdown --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            <x-admin.form-section title="Volume Breakage" description="Transactions below the minimum XP threshold of {{ $this->data->xpThreshold }} XP.">
                <dl class="space-y-3">
                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-800/50">
                        <dt class="text-sm text-slate-600 dark:text-slate-400">Qualifying volume (XP)</dt>
                        <dd class="text-sm font-semibold tabular-nums text-slate-900 dark:text-white">{{ number_format((float) $this->data->qualifyingVolumeXp, 0) }}</dd>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-800/50">
                        <dt class="text-sm text-slate-600 dark:text-slate-400">Wasted volume (below {{ $this->data->xpThreshold }} XP)</dt>
                        <dd class="text-sm font-semibold tabular-nums text-rose-700 dark:text-rose-400">{{ number_format((float) $this->data->wastedVolumeXp, 0) }} ({{ $this->data->wastedPercentage }}%)</dd>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-800/50">
                        <dt class="text-sm text-slate-600 dark:text-slate-400">Sub-threshold transactions</dt>
                        <dd class="text-sm font-semibold tabular-nums text-slate-900 dark:text-white">{{ number_format($this->data->wastedTransactionCount) }}</dd>
                    </div>
                </dl>
            </x-admin.form-section>

            <x-admin.form-section title="Cap Reductions" description="Commission not paid due to viral or global cap enforcement.">
                <dl class="space-y-3">
                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-800/50">
                        <dt class="text-sm text-slate-600 dark:text-slate-400">Viral cap reduction</dt>
                        <dd class="text-sm font-semibold tabular-nums text-orange-700 dark:text-orange-400">
                            ${{ number_format((float) $this->data->viralCapReduction, 2) }}
                            <span class="ml-1 text-xs font-normal text-slate-500">({{ $this->data->viralCapTriggerCount }}x)</span>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-800/50">
                        <dt class="text-sm text-slate-600 dark:text-slate-400">Global cap reduction</dt>
                        <dd class="text-sm font-semibold tabular-nums text-amber-700 dark:text-amber-400">
                            ${{ number_format((float) $this->data->globalCapReduction, 2) }}
                            <span class="ml-1 text-xs font-normal text-slate-500">({{ $this->data->globalCapTriggerCount }}x)</span>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-800/50">
                        <dt class="text-sm text-slate-600 dark:text-slate-400">Clawback total</dt>
                        <dd class="text-sm font-semibold tabular-nums text-rose-700 dark:text-rose-400">
                            ${{ number_format((float) $this->data->clawbackTotal, 2) }}
                            <span class="ml-1 text-xs font-normal text-slate-500">({{ $this->data->clawbackCount }}x)</span>
                        </dd>
                    </div>
                </dl>
            </x-admin.form-section>
        </div>

    </div>
</div>
