{{-- ============================================================
     Income Disclosure Report
     Calls: IncomeDisclosureService via IncomeDisclosureReport
     ============================================================ --}}

<style>
    @media print {
        /* Hide sidebar, top bar, controls, back button, print button */
        aside,
        header,
        .no-print { display: none !important; }
        /* Remove padding injected by admin layout */
        main { padding: 0 !important; }
        .lg\:pl-64 { padding-left: 0 !important; }
        body { background: #fff !important; }
        /* Ensure bar charts print with background */
        .print-bar { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
</style>

<div>
    {{-- ── Date range filter ─────────────────────────────────────────── --}}
    <div class="no-print rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <form wire:submit="regenerate" class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="start-date" class="block text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    From
                </label>
                <input
                    id="start-date"
                    type="date"
                    wire:model="startDate"
                    class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                />
                @error('startDate')
                    <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex-1">
                <label for="end-date" class="block text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    To
                </label>
                <input
                    id="end-date"
                    type="date"
                    wire:model="endDate"
                    class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                />
                @error('endDate')
                    <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex shrink-0 items-center gap-3">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    <span wire:loading wire:target="regenerate">
                        <svg class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </span>
                    <span wire:loading.remove wire:target="regenerate">
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                        </svg>
                    </span>
                    Update Report
                </button>

                <button
                    type="button"
                    onclick="window.print()"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2"
                >
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.056 48.056 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"/>
                    </svg>
                    Print / Export PDF
                </button>
            </div>
        </form>
    </div>

    {{-- ── Loading skeleton ──────────────────────────────────────────── --}}
    <div wire:loading wire:target="regenerate" class="mt-6 space-y-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @for ($i = 0; $i < 5; $i++)
                <div class="h-24 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800"></div>
            @endfor
        </div>
        <div class="h-64 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800"></div>
    </div>

    {{-- ── Report content ────────────────────────────────────────────── --}}
    <div wire:loading.remove wire:target="regenerate" class="mt-6 space-y-6">

        {{-- Report header (visible on print) ────────────────────────── --}}
        <div class="print-only hidden">
            <h1 class="text-2xl font-bold text-slate-900">Income Disclosure Report</h1>
            <p class="mt-1 text-sm text-slate-600">
                {{ $this->company->name }} &middot; Period:
                {{ \Carbon\Carbon::parse($this->report->periodStart)->format('M j, Y') }}
                to
                {{ \Carbon\Carbon::parse($this->report->periodEnd)->format('M j, Y') }}
            </p>
            <p class="mt-1 text-xs text-slate-500">Generated {{ now()->format('M j, Y g:i A') }}</p>
        </div>

        {{-- Key compliance notice ────────────────────────────────────── --}}
        @if ($this->report->totalAffiliates > 0)
            @php
                $zeroEarnerPct = $this->report->zeroEarnerPercentage;
                $noticeColor = $zeroEarnerPct >= 50
                    ? 'border-rose-300 bg-rose-50 text-rose-800 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300'
                    : 'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300';
                $iconColor = $zeroEarnerPct >= 50
                    ? 'text-rose-500 dark:text-rose-400'
                    : 'text-amber-500 dark:text-amber-400';
            @endphp
            <div class="flex items-start gap-3 rounded-xl border px-5 py-4 {{ $noticeColor }}">
                <svg class="mt-0.5 size-5 shrink-0 {{ $iconColor }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                </svg>
                <div>
                    <p class="font-semibold">
                        {{ $this->report->inactiveAffiliates }} out of {{ number_format($this->report->totalAffiliates) }} affiliates ({{ $zeroEarnerPct }}%) earned $0 during this period.
                    </p>
                    <p class="mt-1 text-sm opacity-90">
                        This figure is typically the most scrutinized metric in regulatory income disclosure reviews. It reflects affiliates who were enrolled but did not generate any earnings from commissions or bonuses.
                    </p>
                </div>
            </div>
        @endif

        {{-- ── Summary stats ────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <x-admin.stat-card
                label="Total Affiliates"
                :value="number_format($this->report->totalAffiliates)"
                color="indigo"
            />
            <x-admin.stat-card
                label="Earned > $0"
                :value="number_format($this->report->activeAffiliates) . ' (' . $this->report->activePercentage . '%)'"
                color="emerald"
            />
            <x-admin.stat-card
                label="Earned $0"
                :value="number_format($this->report->inactiveAffiliates) . ' (' . $this->report->zeroEarnerPercentage . '%)'"
                color="rose"
            />
            <x-admin.stat-card
                label="Median Earnings"
                :value="'$' . number_format((float) $this->report->medianEarnings, 2)"
                color="amber"
            />
            <x-admin.stat-card
                label="Total Paid Out"
                :value="'$' . number_format((float) $this->report->totalPaidOut, 2)"
                color="indigo"
            />
        </div>

        {{-- ── Earnings distribution ────────────────────────────────── --}}
        <x-admin.form-section
            title="Earnings Distribution"
            description="How {{ number_format($this->report->totalAffiliates) }} affiliates were distributed across earnings brackets during the selected period."
        >
            @if ($this->report->totalAffiliates === 0)
                <x-admin.empty-state message="No affiliates found for this company." />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th class="pb-3 pr-6 text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Earnings Bracket
                                </th>
                                <th class="pb-3 pr-6 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Affiliates
                                </th>
                                <th class="pb-3 pr-6 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    % of Total
                                </th>
                                <th class="pb-3 text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Proportion
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($this->report->brackets as $bracket)
                                @php
                                    $isZero = $bracket['label'] === '$0';
                                    $barWidth = min(100, (float) $bracket['percentage']);
                                    $rowHighlight = $isZero && $bracket['percentage'] >= 50
                                        ? 'bg-rose-50/60 dark:bg-rose-900/10'
                                        : '';
                                    $barColor = $isZero
                                        ? 'bg-rose-400 dark:bg-rose-500'
                                        : 'bg-indigo-400 dark:bg-indigo-500';
                                    $countColor = $isZero && $bracket['count'] > 0
                                        ? 'font-semibold text-rose-700 dark:text-rose-400'
                                        : 'text-slate-900 dark:text-white';
                                @endphp
                                <tr class="{{ $rowHighlight }}">
                                    <td class="py-3 pr-6 font-medium text-slate-900 dark:text-white">
                                        {{ $bracket['label'] }}
                                        @if ($isZero)
                                            <span class="ml-2 rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-rose-700 dark:bg-rose-900/40 dark:text-rose-400">
                                                No earnings
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 pr-6 text-right tabular-nums {{ $countColor }}">
                                        {{ number_format($bracket['count']) }}
                                    </td>
                                    <td class="py-3 pr-6 text-right tabular-nums text-slate-700 dark:text-slate-300">
                                        {{ number_format($bracket['percentage'], 1) }}%
                                    </td>
                                    <td class="py-3 w-full min-w-[8rem]">
                                        <div class="h-4 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                            <div
                                                class="print-bar h-4 rounded-full transition-all duration-500 {{ $barColor }}"
                                                style="width: {{ $barWidth }}%;"
                                                role="progressbar"
                                                aria-valuenow="{{ $barWidth }}"
                                                aria-valuemin="0"
                                                aria-valuemax="100"
                                                aria-label="{{ $bracket['label'] }}: {{ $bracket['percentage'] }}%"
                                            ></div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-admin.form-section>

        {{-- ── Averages and thresholds ──────────────────────────────── --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            {{-- Averages --}}
            <x-admin.form-section title="Earnings Averages" description="Calculated across all enrolled affiliates, including those who earned $0.">
                <dl class="space-y-4">
                    <div class="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/50">
                        <dt class="text-sm text-slate-600 dark:text-slate-400">Mean (average) earnings</dt>
                        <dd class="text-lg font-semibold tabular-nums text-slate-900 dark:text-white">
                            ${{ number_format((float) $this->report->meanEarnings, 2) }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/50">
                        <dt class="text-sm text-slate-600 dark:text-slate-400">Median earnings (middle value)</dt>
                        <dd class="text-lg font-semibold tabular-nums text-slate-900 dark:text-white">
                            ${{ number_format((float) $this->report->medianEarnings, 2) }}
                        </dd>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        When the median is much lower than the mean, it indicates a small group of top earners is pulling the average up. Most affiliates earn at or below the median.
                    </p>
                </dl>
            </x-admin.form-section>

            {{-- Top earner thresholds --}}
            <x-admin.form-section title="Top Earner Thresholds" description="The minimum earnings required to be in the top 1% and top 10% of all affiliates.">
                <dl class="space-y-4">
                    <div class="flex items-center justify-between rounded-lg border border-amber-100 bg-amber-50 px-4 py-3 dark:border-amber-800/40 dark:bg-amber-900/10">
                        <div>
                            <dt class="text-sm font-medium text-amber-800 dark:text-amber-300">Top 1% earn above</dt>
                            <p class="mt-0.5 text-xs text-amber-700 dark:text-amber-400">
                                The {{ (int) ceil($this->report->totalAffiliates / 100) }} highest-earning affiliate(s)
                            </p>
                        </div>
                        <dd class="text-xl font-bold tabular-nums text-amber-700 dark:text-amber-300">
                            ${{ number_format((float) $this->report->top1PercentThreshold, 2) }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between rounded-lg border border-indigo-100 bg-indigo-50 px-4 py-3 dark:border-indigo-800/40 dark:bg-indigo-900/10">
                        <div>
                            <dt class="text-sm font-medium text-indigo-800 dark:text-indigo-300">Top 10% earn above</dt>
                            <p class="mt-0.5 text-xs text-indigo-700 dark:text-indigo-400">
                                The {{ (int) ceil($this->report->totalAffiliates / 10) }} highest-earning affiliate(s)
                            </p>
                        </div>
                        <dd class="text-xl font-bold tabular-nums text-indigo-700 dark:text-indigo-300">
                            ${{ number_format((float) $this->report->top10PercentThreshold, 2) }}
                        </dd>
                    </div>
                </dl>
            </x-admin.form-section>
        </div>

        {{-- ── Plain-English summary (print-friendly) ──────────────── --}}
        <x-admin.form-section title="Plain-English Summary" description="Suitable for inclusion in regulatory filings or public disclosures.">
            <div class="prose prose-sm max-w-none text-slate-700 dark:prose-invert dark:text-slate-300">
                <p>
                    During the period from
                    <strong>{{ \Carbon\Carbon::parse($this->report->periodStart)->format('F j, Y') }}</strong>
                    to
                    <strong>{{ \Carbon\Carbon::parse($this->report->periodEnd)->format('F j, Y') }}</strong>,
                    <strong>{{ $this->company->name }}</strong> had
                    <strong>{{ number_format($this->report->totalAffiliates) }}</strong> enrolled affiliate(s).
                </p>

                <p class="mt-3">
                    Of those,
                    <strong>{{ number_format($this->report->activeAffiliates) }} ({{ $this->report->activePercentage }}%)</strong>
                    received at least some commission or bonus income.
                    <strong>{{ number_format($this->report->inactiveAffiliates) }} ({{ $this->report->zeroEarnerPercentage }}%)</strong>
                    earned <strong>$0</strong> during this period.
                </p>

                <p class="mt-3">
                    The <strong>average (mean) earnings</strong> across all affiliates — including those who earned nothing — was
                    <strong>${{ number_format((float) $this->report->meanEarnings, 2) }}</strong>.
                    The <strong>median earnings</strong> (the midpoint where half of affiliates earned more and half earned less) was
                    <strong>${{ number_format((float) $this->report->medianEarnings, 2) }}</strong>.
                </p>

                <p class="mt-3">
                    The top 1% of earners made more than
                    <strong>${{ number_format((float) $this->report->top1PercentThreshold, 2) }}</strong>.
                    The top 10% of earners made more than
                    <strong>${{ number_format((float) $this->report->top10PercentThreshold, 2) }}</strong>.
                </p>

                <p class="mt-3">
                    Total commissions and bonuses paid out during this period:
                    <strong>${{ number_format((float) $this->report->totalPaidOut, 2) }}</strong>.
                </p>

                <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">
                    These results are typical of the industry and are not a guarantee of income. Individual results will vary based on effort, experience, and market conditions. This disclosure is generated automatically from the NGE commission and bonus ledger.
                </p>
            </div>
        </x-admin.form-section>

    </div>{{-- end wire:loading.remove --}}
</div>
