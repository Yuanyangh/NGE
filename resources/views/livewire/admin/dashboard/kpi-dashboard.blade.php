{{-- ============================================================
     KPI Dashboard
     Calls: KpiDashboardService + BreakageAnalysisService
     ============================================================ --}}

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

    {{-- ── Date range filter ────────────────────────────────────────── --}}
    <div class="no-print rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <form wire:submit="regenerate" class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="kpi-start" class="block text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">From</label>
                <input id="kpi-start" type="date" wire:model="startDate"
                    class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white"/>
                @error('startDate') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex-1">
                <label for="kpi-end" class="block text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">To</label>
                <input id="kpi-end" type="date" wire:model="endDate"
                    class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white"/>
                @error('endDate') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex shrink-0 items-center gap-3">
                <button type="submit" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <svg wire:loading wire:target="regenerate" class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <svg wire:loading.remove wire:target="regenerate" class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                    </svg>
                    Update
                </button>
                <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.056 48.056 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"/>
                    </svg>
                    Print
                </button>
            </div>
        </form>
    </div>

    {{-- Loading skeleton --}}
    <div wire:loading wire:target="regenerate" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @for ($i = 0; $i < 8; $i++)
            <div class="h-24 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800"></div>
        @endfor
    </div>

    <div wire:loading.remove wire:target="regenerate" class="space-y-6">

        {{-- Viral cap warning --}}
        @if ($this->kpi->viralCapTriggeredCount > 0)
            <div class="flex items-start gap-3 rounded-xl border border-orange-300 bg-orange-50 px-5 py-4 dark:border-orange-700 dark:bg-orange-900/20">
                <svg class="mt-0.5 size-5 shrink-0 text-orange-500 dark:text-orange-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                </svg>
                <div>
                    <p class="font-semibold text-orange-800 dark:text-orange-300">
                        Viral QVV cap triggered {{ $this->kpi->viralCapTriggeredCount }} time(s) this period
                    </p>
                    <p class="mt-1 text-sm text-orange-700 dark:text-orange-400">
                        Total cap reduction: ${{ number_format((float) $this->breakage->totalCapReduction, 2) }} — review the Cap Impact report for details.
                    </p>
                </div>
            </div>
        @endif

        {{-- ── Primary KPI cards ─────────────────────────────────────── --}}
        @php
            $makeChange = function (string $pct): array {
                $isUp = bccomp($pct, '0', 2) >= 0;
                return [
                    'pct'  => abs((float) $pct),
                    'isUp' => $isUp,
                    'label'=> abs((float) $pct) . '%',
                ];
            };
            $vc = $makeChange($this->kpi->volumeChange);
            $cc = $makeChange($this->kpi->commissionChange);
            $ac = $makeChange($this->kpi->affiliateChange);
            $ec = $makeChange($this->kpi->enrollmentChange);
        @endphp

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-admin.stat-card
                label="Total Volume (XP)"
                :value="number_format((float) $this->kpi->totalVolume, 0)"
                :trend="$vc['label']"
                :trend-up="$vc['isUp']"
                color="indigo"
            />
            <x-admin.stat-card
                label="Total Commissions"
                :value="'$' . number_format((float) $this->kpi->totalCommissions, 2)"
                :trend="$cc['label']"
                :trend-up="$cc['isUp']"
                color="emerald"
            />
            <x-admin.stat-card
                label="Active Affiliates"
                :value="number_format($this->kpi->activeAffiliates) . ' / ' . number_format($this->kpi->totalAffiliates)"
                :trend="$ac['label']"
                :trend-up="$ac['isUp']"
                color="amber"
            />
            <x-admin.stat-card
                label="New Enrollments"
                :value="number_format($this->kpi->newEnrollments)"
                :trend="$ec['label']"
                :trend-up="$ec['isUp']"
                color="rose"
            />
        </div>

        {{-- Secondary KPI row --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-admin.stat-card
                label="Total Bonuses"
                :value="'$' . number_format((float) $this->kpi->totalBonuses, 2)"
                color="violet"
            />
            <x-admin.stat-card
                label="Payout Ratio"
                :value="$this->kpi->payoutRatio . '%'"
                color="sky"
            />
            <x-admin.stat-card
                label="Active Customers"
                :value="number_format($this->kpi->activeCustomers)"
                color="teal"
            />
            <x-admin.stat-card
                label="Commission Runs"
                :value="number_format($this->kpi->commissionRunCount)"
                color="indigo"
            />
        </div>

        {{-- ── Charts row ────────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            {{-- Volume trend --}}
            <x-admin.form-section title="Daily Volume (XP)" description="Confirmed transactions with qualifying XP over the selected period.">
                @if (empty($this->kpi->volumeTrend))
                    <x-admin.empty-state message="No volume data for this period." />
                @else
                    @php
                        $maxVol = max(array_column($this->kpi->volumeTrend, 'amount') + [1]);
                    @endphp
                    <div class="flex h-40 items-end gap-0.5 overflow-x-auto">
                        @foreach ($this->kpi->volumeTrend as $point)
                            @php
                                $pct = (int) round(((float) $point['amount'] / (float) $maxVol) * 100);
                                $pct = max(1, $pct);
                            @endphp
                            <div class="group relative flex-1 min-w-[6px]" title="{{ $point['date'] }}: {{ number_format((float) $point['amount'], 0) }} XP">
                                <div
                                    class="print-bar w-full rounded-t bg-indigo-400 transition-all duration-300 group-hover:bg-indigo-600 dark:bg-indigo-500 dark:group-hover:bg-indigo-400"
                                    style="height: {{ $pct }}%;"
                                ></div>
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                        {{ count($this->kpi->volumeTrend) }} days shown — hover bars for details.
                    </p>
                @endif
            </x-admin.form-section>

            {{-- Payout trend --}}
            <x-admin.form-section title="Daily Payout ($)" description="Total commissions + bonuses per run date.">
                @if (empty($this->kpi->payoutTrend))
                    <x-admin.empty-state message="No payout data for this period." />
                @else
                    @php
                        $maxPayout = max(array_column($this->kpi->payoutTrend, 'amount') + [1]);
                    @endphp
                    <div class="flex h-40 items-end gap-0.5 overflow-x-auto">
                        @foreach ($this->kpi->payoutTrend as $point)
                            @php
                                $pct = (int) round(((float) $point['amount'] / (float) $maxPayout) * 100);
                                $pct = max(1, $pct);
                            @endphp
                            <div class="group relative flex-1 min-w-[6px]" title="{{ $point['date'] }}: ${{ number_format((float) $point['amount'], 2) }}">
                                <div
                                    class="print-bar w-full rounded-t bg-emerald-400 transition-all duration-300 group-hover:bg-emerald-600 dark:bg-emerald-500 dark:group-hover:bg-emerald-400"
                                    style="height: {{ $pct }}%;"
                                ></div>
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                        {{ count($this->kpi->payoutTrend) }} data points shown.
                    </p>
                @endif
            </x-admin.form-section>
        </div>

        {{-- ── Top earners + Breakage ────────────────────────────────── --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            {{-- Top 5 earners --}}
            <x-admin.form-section title="Top 5 Earners" description="Highest total commission + bonus earners for this period.">
                @if (empty($this->kpi->topEarners))
                    <x-admin.empty-state message="No earners found for this period." />
                @else
                    @php $maxEarning = max(array_column($this->kpi->topEarners, 'total_earnings') + ['1']); @endphp
                    <div class="space-y-3">
                        @foreach ($this->kpi->topEarners as $i => $earner)
                            @php
                                $barPct = (int) round(((float) $earner['total_earnings'] / (float) $maxEarning) * 100);
                            @endphp
                            <div>
                                <div class="mb-1 flex items-center justify-between text-sm">
                                    <span class="flex items-center gap-2 font-medium text-slate-900 dark:text-white">
                                        <span class="flex size-5 items-center justify-center rounded-full bg-indigo-100 text-[10px] font-bold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">{{ $i + 1 }}</span>
                                        {{ $earner['name'] }}
                                    </span>
                                    <span class="tabular-nums text-slate-700 dark:text-slate-300">${{ number_format((float) $earner['total_earnings'], 2) }}</span>
                                </div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                    <div class="print-bar h-2 rounded-full bg-indigo-400 dark:bg-indigo-500" style="width: {{ $barPct }}%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-admin.form-section>

            {{-- Breakage summary --}}
            <x-admin.form-section title="Breakage Summary" description="Wasted volume and cap reductions — money not paid out.">
                <dl class="space-y-3">
                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-800/50">
                        <dt class="text-sm text-slate-600 dark:text-slate-400">Wasted volume (XP)</dt>
                        <dd class="text-sm font-semibold tabular-nums text-rose-700 dark:text-rose-400">
                            {{ number_format((float) $this->breakage->wastedVolumeXp, 0) }} XP ({{ $this->breakage->wastedPercentage }}%)
                        </dd>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-800/50">
                        <dt class="text-sm text-slate-600 dark:text-slate-400">Viral cap reduction</dt>
                        <dd class="text-sm font-semibold tabular-nums text-orange-700 dark:text-orange-400">
                            ${{ number_format((float) $this->breakage->viralCapReduction, 2) }}
                            <span class="ml-1 text-xs font-normal text-slate-500">({{ $this->breakage->viralCapTriggerCount }}x)</span>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-800/50">
                        <dt class="text-sm text-slate-600 dark:text-slate-400">Global cap reduction</dt>
                        <dd class="text-sm font-semibold tabular-nums text-amber-700 dark:text-amber-400">
                            ${{ number_format((float) $this->breakage->globalCapReduction, 2) }}
                            <span class="ml-1 text-xs font-normal text-slate-500">({{ $this->breakage->globalCapTriggerCount }}x)</span>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-800/50">
                        <dt class="text-sm text-slate-600 dark:text-slate-400">Clawbacks</dt>
                        <dd class="text-sm font-semibold tabular-nums text-slate-700 dark:text-slate-300">
                            ${{ number_format((float) $this->breakage->clawbackTotal, 2) }}
                            <span class="ml-1 text-xs font-normal text-slate-500">({{ $this->breakage->clawbackCount }}x)</span>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3 dark:border-slate-700">
                        <dt class="text-sm font-medium text-slate-700 dark:text-slate-300">Breakage rate</dt>
                        <dd class="text-lg font-bold tabular-nums {{ (float) $this->breakage->breakageRate >= 10 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' }}">
                            {{ $this->breakage->breakageRate }}%
                        </dd>
                    </div>
                </dl>
            </x-admin.form-section>
        </div>

    </div>{{-- end wire:loading.remove --}}
</div>
