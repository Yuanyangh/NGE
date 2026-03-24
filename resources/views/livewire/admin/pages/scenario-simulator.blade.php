<div>
    <x-admin.page-header
        title="Scenario Simulator"
        description="Project network growth, commissions, and sustainability over time."
    />

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-800 dark:bg-rose-500/10 dark:text-rose-400">
            {{ session('error') }}
        </div>
    @endif

    {{-- Validation errors --}}
    @if ($errors->any())
        <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 dark:border-rose-800 dark:bg-rose-500/10">
            <ul class="list-inside list-disc space-y-1 text-sm text-rose-700 dark:text-rose-400">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Simulation Form --}}
    <form wire:submit="runSimulation" class="mt-6 space-y-6">

        {{-- Section 1: Configuration --}}
        <x-admin.form-section title="Configuration">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {{-- Company (needs wire:model.live for reactive cascade) --}}
                <div>
                    <label for="company_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Company <span class="text-rose-500">*</span>
                    </label>
                    <select
                        id="company_id"
                        wire:model.live="company_id"
                        required
                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                    >
                        <option value="">Select a company...</option>
                        @foreach ($this->companies as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('company_id')
                        <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Compensation Plan (dependent on company) --}}
                <div>
                    <label for="compensation_plan_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Compensation Plan <span class="text-rose-500">*</span>
                    </label>
                    <select
                        id="compensation_plan_id"
                        wire:model="compensation_plan_id"
                        required
                        @if (! $company_id) disabled @endif
                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                    >
                        <option value="">{{ $company_id ? 'Select a plan...' : 'Select company first' }}</option>
                        @foreach ($this->plans as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('compensation_plan_id')
                        <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <x-admin.input name="simulation_name" label="Simulation Name" wire="simulation_name" required />

                <x-admin.select
                    name="projection_days"
                    label="Projection Days"
                    wire="projection_days"
                    :options="[30 => '30 days', 60 => '60 days', 90 => '90 days', 180 => '180 days', 365 => '365 days']"
                    required
                />

                <x-admin.input name="seed" label="Random Seed" type="number" wire="seed" required />
            </div>
        </x-admin.form-section>

        {{-- Section 2: Starting Network --}}
        <x-admin.form-section title="Starting Network">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-admin.input name="starting_affiliates" label="Starting Affiliates" type="number" wire="starting_affiliates" required />
                <x-admin.input name="starting_customers" label="Starting Customers" type="number" wire="starting_customers" required />
            </div>
        </x-admin.form-section>

        {{-- Section 3: Growth Assumptions --}}
        <x-admin.form-section title="Growth Assumptions">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-admin.input name="new_affiliates_per_day" label="New Affiliates/Day" type="number" wire="new_affiliates_per_day" required step="0.1" />
                <x-admin.input name="new_customers_per_affiliate_per_month" label="New Customers/Affiliate/Month" type="number" wire="new_customers_per_affiliate_per_month" required step="0.1" />
                <div>
                    <x-admin.input name="affiliate_to_customer_ratio" label="Affiliate-to-Customer Ratio" type="number" wire="affiliate_to_customer_ratio" required step="0.01" />
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Fraction of new customers that convert to affiliates</p>
                </div>
                <x-admin.select
                    name="growth_curve"
                    label="Growth Curve"
                    wire="growth_curve"
                    :options="['linear' => 'Linear', 'exponential' => 'Exponential', 'logarithmic' => 'Logarithmic']"
                    required
                />
            </div>
        </x-admin.form-section>

        {{-- Section 4: Transaction Assumptions --}}
        <x-admin.form-section title="Transaction Assumptions">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-admin.input name="average_order_xp" label="Average Order XP" type="number" wire="average_order_xp" required step="0.01" />
                <x-admin.input name="orders_per_customer_per_month" label="Orders/Customer/Month" type="number" wire="orders_per_customer_per_month" required step="0.1" />
                <x-admin.input name="smartship_adoption_rate" label="SmartShip Adoption Rate" type="number" wire="smartship_adoption_rate" required step="0.01" />
                <x-admin.input name="smartship_average_xp" label="SmartShip Average XP" type="number" wire="smartship_average_xp" required step="0.01" />
                <x-admin.input name="refund_rate" label="Refund Rate" type="number" wire="refund_rate" required step="0.01" />
            </div>
        </x-admin.form-section>

        {{-- Section 5: Retention --}}
        <x-admin.form-section title="Retention">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-admin.input name="customer_monthly_churn_rate" label="Customer Monthly Churn Rate" type="number" wire="customer_monthly_churn_rate" required step="0.01" />
                <x-admin.input name="affiliate_monthly_churn_rate" label="Affiliate Monthly Churn Rate" type="number" wire="affiliate_monthly_churn_rate" required step="0.01" />
            </div>
        </x-admin.form-section>

        {{-- Section 6: Tree Shape --}}
        <x-admin.form-section title="Tree Shape">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-admin.input name="average_legs_per_affiliate" label="Average Legs/Affiliate" type="number" wire="average_legs_per_affiliate" required />
                <div>
                    <x-admin.input name="leg_balance_ratio" label="Leg Balance Ratio" type="number" wire="leg_balance_ratio" required step="0.1" />
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">0.0 = mega-leg, 1.0 = perfectly balanced</p>
                </div>
                <x-admin.select
                    name="depth_bias"
                    label="Depth Bias"
                    wire="depth_bias"
                    :options="['shallow' => 'Shallow', 'moderate' => 'Moderate', 'deep' => 'Deep']"
                    required
                />
            </div>
        </x-admin.form-section>

        {{-- Submit --}}
        <div class="flex items-center gap-4">
            <button
                type="submit"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-offset-slate-900"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="runSimulation">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z"/></svg>
                </span>
                <span wire:loading wire:target="runSimulation">
                    <svg class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                </span>
                <span wire:loading.remove wire:target="runSimulation">Run Simulation</span>
                <span wire:loading wire:target="runSimulation">Running...</span>
            </button>
        </div>
    </form>

    {{-- ==================== RESULTS ==================== --}}
    @if ($results)
        <div class="mt-10 space-y-6">
            {{-- Results header with controls --}}
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Results</h2>
                <div class="flex flex-wrap items-center gap-3">
                    {{-- Export CSV --}}
                    <button
                        wire:click="exportCsv"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                    >
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                        Export CSV
                    </button>

                    {{-- Compare with past runs --}}
                    @if (count($this->savedRuns) > 0)
                        <div class="flex items-center gap-2">
                            <select
                                wire:model="compare_run_id"
                                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                            >
                                <option value="">Compare with...</option>
                                @foreach ($this->savedRuns as $runId => $runLabel)
                                    <option value="{{ $runId }}">{{ $runLabel }}</option>
                                @endforeach
                            </select>
                            <button
                                wire:click="loadPastRun"
                                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                            >
                                Load
                            </button>
                            @if ($compareResults)
                                <button
                                    wire:click="clearComparison"
                                    class="inline-flex items-center rounded-lg border border-rose-300 bg-white px-3 py-2 text-sm font-medium text-rose-700 shadow-sm transition-colors hover:bg-rose-50 dark:border-rose-600 dark:bg-slate-800 dark:text-rose-400 dark:hover:bg-slate-700"
                                >
                                    Clear
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Summary stat cards (row 1: 4 cards) --}}
            @php
                $summary = $results['summary'] ?? [];
                $risk = $results['risk_indicators'] ?? [];
                $compareSummary = $compareResults['summary'] ?? null;
                $compareRisk = $compareResults['risk_indicators'] ?? null;

                $sustainScore = $risk['sustainability_score'] ?? 0;
                $sustainColor = $sustainScore >= 70 ? 'emerald' : ($sustainScore >= 40 ? 'amber' : 'rose');
            @endphp

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-admin.stat-card
                    label="Total Payout"
                    :value="'$' . number_format($summary['total_payout'] ?? 0, 2)"
                    icon="banknotes"
                    color="indigo"
                />
                <x-admin.stat-card
                    label="Payout Ratio"
                    :value="number_format($summary['payout_ratio_percent'] ?? 0, 1) . '%'"
                    icon="chart-pie"
                    color="indigo"
                />
                <x-admin.stat-card
                    label="Cap Trigger Days"
                    :value="($summary['viral_cap_triggered_days'] ?? 0) . ' viral / ' . ($summary['global_cap_triggered_days'] ?? 0) . ' global'"
                    icon="shield-exclamation"
                    color="amber"
                />
                <div class="relative rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-start gap-4">
                        <div class="flex size-11 shrink-0 items-center justify-center rounded-lg ring-1
                            {{ $sustainColor === 'emerald' ? 'bg-emerald-50 ring-emerald-500/20 dark:bg-emerald-500/10 dark:ring-emerald-400/20' : '' }}
                            {{ $sustainColor === 'amber' ? 'bg-amber-50 ring-amber-500/20 dark:bg-amber-500/10 dark:ring-amber-400/20' : '' }}
                            {{ $sustainColor === 'rose' ? 'bg-rose-50 ring-rose-500/20 dark:bg-rose-500/10 dark:ring-rose-400/20' : '' }}
                        ">
                            <svg class="size-5
                                {{ $sustainColor === 'emerald' ? 'text-emerald-600 dark:text-emerald-400' : '' }}
                                {{ $sustainColor === 'amber' ? 'text-amber-600 dark:text-amber-400' : '' }}
                                {{ $sustainColor === 'rose' ? 'text-rose-600 dark:text-rose-400' : '' }}
                            " xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ $sustainScore }} / 100</p>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Sustainability Score</p>
                            @if ($compareSummary)
                                @php $compareScore = $compareRisk['sustainability_score'] ?? 0; @endphp
                                <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">vs {{ $compareScore }} / 100</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Summary stat cards (row 2: 3 cards) --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-admin.stat-card
                    label="Total Projected Volume"
                    :value="'$' . number_format($summary['total_volume'] ?? 0, 2)"
                    icon="arrow-trending-up"
                    color="emerald"
                />
                <x-admin.stat-card
                    label="Avg Affiliate Earning/Day"
                    :value="'$' . number_format($summary['avg_affiliate_earning_per_day'] ?? 0, 2)"
                    icon="user-group"
                    color="indigo"
                />
                <x-admin.stat-card
                    label="Top Earner Concentration"
                    :value="number_format($risk['top_earner_concentration_percent'] ?? 0, 1) . '%'"
                    icon="chart-bar"
                    :color="($risk['top_earner_concentration_percent'] ?? 0) > 50 ? 'rose' : (($risk['top_earner_concentration_percent'] ?? 0) > 30 ? 'amber' : 'emerald')"
                />
            </div>

            {{-- Comparison callouts --}}
            @if ($compareSummary)
                <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-800 dark:bg-indigo-500/10">
                    <h3 class="text-sm font-semibold text-indigo-900 dark:text-indigo-300">Comparison Delta</h3>
                    <div class="mt-2 grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                        <div>
                            <p class="text-indigo-600 dark:text-indigo-400">Total Payout</p>
                            <p class="font-medium text-indigo-900 dark:text-indigo-200">
                                vs ${{ number_format($compareSummary['total_payout'] ?? 0, 2) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-indigo-600 dark:text-indigo-400">Payout Ratio</p>
                            <p class="font-medium text-indigo-900 dark:text-indigo-200">
                                vs {{ number_format($compareSummary['payout_ratio_percent'] ?? 0, 1) }}%
                            </p>
                        </div>
                        <div>
                            <p class="text-indigo-600 dark:text-indigo-400">Total Volume</p>
                            <p class="font-medium text-indigo-900 dark:text-indigo-200">
                                vs ${{ number_format($compareSummary['total_volume'] ?? 0, 2) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-indigo-600 dark:text-indigo-400">Avg Earning/Day</p>
                            <p class="font-medium text-indigo-900 dark:text-indigo-200">
                                vs ${{ number_format($compareSummary['avg_affiliate_earning_per_day'] ?? 0, 2) }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Risk Indicators --}}
            <x-admin.form-section title="Risk Indicators">
                @php
                    $payoutTrend = $risk['payout_trend'] ?? 'stable';
                    $capFrequency = $risk['cap_frequency'] ?? 'none';
                    $topEarnerConc = $risk['top_earner_concentration'] ?? 'low';

                    $trendColor = in_array($payoutTrend, ['stable', 'decreasing']) ? 'success' : 'danger';
                    $capColor = in_array($capFrequency, ['none', 'rare']) ? 'success' : ($capFrequency === 'occasional' ? 'warning' : 'danger');
                    $concColor = $topEarnerConc === 'low' ? 'success' : ($topEarnerConc === 'moderate' ? 'warning' : 'danger');
                    $sustainBadge = $sustainScore >= 70 ? 'success' : ($sustainScore >= 40 ? 'warning' : 'danger');
                @endphp
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-800/50">
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Payout Trend</span>
                        <x-admin.badge :color="$trendColor" size="md">{{ ucfirst($payoutTrend) }}</x-admin.badge>
                    </div>
                    <div class="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-800/50">
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Cap Frequency</span>
                        <x-admin.badge :color="$capColor" size="md">{{ ucfirst($capFrequency) }}</x-admin.badge>
                    </div>
                    <div class="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-800/50">
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Top Earner Conc.</span>
                        <x-admin.badge :color="$concColor" size="md">{{ ucfirst($topEarnerConc) }}</x-admin.badge>
                    </div>
                    <div class="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-800/50">
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Sustainability</span>
                        <x-admin.badge :color="$sustainBadge" size="md">{{ $sustainScore }} / 100</x-admin.badge>
                    </div>
                </div>
            </x-admin.form-section>

            {{-- Charts --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {{-- Payout Ratio Chart --}}
                <div
                    wire:ignore
                    x-data="{
                        chart: null,
                        initChart() {
                            if (this.chart) { this.chart.destroy(); }
                            const ctx = this.$refs.payoutCanvas.getContext('2d');
                            const isDark = document.documentElement.classList.contains('dark');
                            const gridColor = isDark ? 'rgba(148,163,184,0.1)' : 'rgba(148,163,184,0.2)';
                            const textColor = isDark ? '#94a3b8' : '#64748b';

                            const dailyData = @js($results['daily_projections'] ?? []);
                            const labels = dailyData.map(d => d.date);
                            const payoutRatios = dailyData.map(d => d.payout_ratio_percent);

                            const datasets = [{
                                label: 'Payout Ratio %',
                                data: payoutRatios,
                                borderColor: '#6366f1',
                                backgroundColor: 'rgba(99,102,241,0.08)',
                                borderWidth: 2,
                                tension: 0.3,
                                fill: true,
                                pointRadius: 0,
                                pointHitRadius: 8,
                            }];

                            const compareData = @js($compareResults['daily_projections'] ?? []);
                            if (compareData.length) {
                                datasets.push({
                                    label: 'Comparison',
                                    data: compareData.map(d => d.payout_ratio_percent),
                                    borderColor: '#94a3b8',
                                    borderWidth: 1.5,
                                    borderDash: [5, 3],
                                    tension: 0.3,
                                    fill: false,
                                    pointRadius: 0,
                                    pointHitRadius: 8,
                                });
                            }

                            this.chart = new Chart(ctx, {
                                type: 'line',
                                data: { labels, datasets },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    interaction: { mode: 'index', intersect: false },
                                    plugins: {
                                        legend: { labels: { color: textColor, usePointStyle: true, padding: 16 } },
                                        tooltip: { backgroundColor: isDark ? '#1e293b' : '#fff', titleColor: isDark ? '#e2e8f0' : '#1e293b', bodyColor: isDark ? '#cbd5e1' : '#475569', borderColor: isDark ? '#334155' : '#e2e8f0', borderWidth: 1 },
                                    },
                                    scales: {
                                        x: { grid: { color: gridColor }, ticks: { color: textColor, maxTicksLimit: 10 } },
                                        y: { grid: { color: gridColor }, ticks: { color: textColor, callback: v => v + '%' }, beginAtZero: true },
                                    },
                                },
                            });
                        }
                    }"
                    x-init="$nextTick(() => { if (@js($results !== null)) initChart(); })"
                    @simulation-complete.window="$nextTick(() => initChart())"
                    class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <h3 class="mb-4 text-sm font-semibold text-slate-900 dark:text-white">Daily Payout Ratio (%)</h3>
                    <div class="h-72">
                        <canvas x-ref="payoutCanvas"></canvas>
                    </div>
                </div>

                {{-- Commissions Chart --}}
                <div
                    wire:ignore
                    x-data="{
                        chart: null,
                        initChart() {
                            if (this.chart) { this.chart.destroy(); }
                            const ctx = this.$refs.commissionsCanvas.getContext('2d');
                            const isDark = document.documentElement.classList.contains('dark');
                            const gridColor = isDark ? 'rgba(148,163,184,0.1)' : 'rgba(148,163,184,0.2)';
                            const textColor = isDark ? '#94a3b8' : '#64748b';

                            const dailyData = @js($results['daily_projections'] ?? []);
                            const labels = dailyData.map(d => d.date);

                            const datasets = [
                                {
                                    label: 'Affiliate',
                                    data: dailyData.map(d => d.affiliate_commissions),
                                    borderColor: '#6366f1',
                                    backgroundColor: 'rgba(99,102,241,0.15)',
                                    borderWidth: 1.5,
                                    tension: 0.3,
                                    fill: true,
                                    pointRadius: 0,
                                    pointHitRadius: 8,
                                },
                                {
                                    label: 'Viral',
                                    data: dailyData.map(d => d.viral_commissions),
                                    borderColor: '#10b981',
                                    backgroundColor: 'rgba(16,185,129,0.15)',
                                    borderWidth: 1.5,
                                    tension: 0.3,
                                    fill: true,
                                    pointRadius: 0,
                                    pointHitRadius: 8,
                                },
                            ];

                            const compareData = @js($compareResults['daily_projections'] ?? []);
                            if (compareData.length) {
                                datasets.push(
                                    {
                                        label: 'Affiliate (cmp)',
                                        data: compareData.map(d => d.affiliate_commissions),
                                        borderColor: '#94a3b8',
                                        borderWidth: 1,
                                        borderDash: [5, 3],
                                        tension: 0.3,
                                        fill: false,
                                        pointRadius: 0,
                                    },
                                    {
                                        label: 'Viral (cmp)',
                                        data: compareData.map(d => d.viral_commissions),
                                        borderColor: '#64748b',
                                        borderWidth: 1,
                                        borderDash: [5, 3],
                                        tension: 0.3,
                                        fill: false,
                                        pointRadius: 0,
                                    }
                                );
                            }

                            this.chart = new Chart(ctx, {
                                type: 'line',
                                data: { labels, datasets },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    interaction: { mode: 'index', intersect: false },
                                    plugins: {
                                        legend: { labels: { color: textColor, usePointStyle: true, padding: 16 } },
                                        tooltip: { backgroundColor: isDark ? '#1e293b' : '#fff', titleColor: isDark ? '#e2e8f0' : '#1e293b', bodyColor: isDark ? '#cbd5e1' : '#475569', borderColor: isDark ? '#334155' : '#e2e8f0', borderWidth: 1 },
                                    },
                                    scales: {
                                        x: { grid: { color: gridColor }, ticks: { color: textColor, maxTicksLimit: 10 } },
                                        y: { grid: { color: gridColor }, ticks: { color: textColor, callback: v => '$' + v.toLocaleString() }, beginAtZero: true, stacked: true },
                                    },
                                },
                            });
                        }
                    }"
                    x-init="$nextTick(() => { if (@js($results !== null)) initChart(); })"
                    @simulation-complete.window="$nextTick(() => initChart())"
                    class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <h3 class="mb-4 text-sm font-semibold text-slate-900 dark:text-white">Daily Commissions (Affiliate vs Viral)</h3>
                    <div class="h-72">
                        <canvas x-ref="commissionsCanvas"></canvas>
                    </div>
                </div>
            </div>

            {{-- Tier Distribution Tables --}}
            @if (! empty($results['tier_distribution']['affiliate_tiers'] ?? []))
                <x-admin.form-section title="Affiliate Tier Distribution">
                    <x-admin.data-table>
                        <x-slot:header>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Tier Rate</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Affiliate-Days</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Paid</th>
                        </x-slot:header>
                        <x-slot:body>
                            @foreach ($results['tier_distribution']['affiliate_tiers'] as $tier)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                    <td class="px-4 py-3 text-sm text-slate-900 dark:text-slate-200">{{ number_format($tier['tier_rate'] * 100, 1) }}%</td>
                                    <td class="px-4 py-3 text-right text-sm tabular-nums text-slate-700 dark:text-slate-300">{{ number_format($tier['affiliate_count']) }}</td>
                                    <td class="px-4 py-3 text-right text-sm tabular-nums font-medium text-slate-900 dark:text-white">${{ number_format($tier['total_paid'], 2) }}</td>
                                </tr>
                            @endforeach
                        </x-slot:body>
                    </x-admin.data-table>
                </x-admin.form-section>
            @endif

            @if (! empty($results['tier_distribution']['viral_tiers'] ?? []))
                <x-admin.form-section title="Viral Tier Distribution">
                    <x-admin.data-table>
                        <x-slot:header>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Tier</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Affiliate-Days</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Paid</th>
                        </x-slot:header>
                        <x-slot:body>
                            @foreach ($results['tier_distribution']['viral_tiers'] as $tier)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                    <td class="px-4 py-3 text-sm text-slate-900 dark:text-slate-200">{{ $tier['tier'] }}</td>
                                    <td class="px-4 py-3 text-right text-sm tabular-nums text-slate-700 dark:text-slate-300">{{ number_format($tier['affiliate_count']) }}</td>
                                    <td class="px-4 py-3 text-right text-sm tabular-nums font-medium text-slate-900 dark:text-white">${{ number_format($tier['total_paid'], 2) }}</td>
                                </tr>
                            @endforeach
                        </x-slot:body>
                    </x-admin.data-table>
                </x-admin.form-section>
            @endif
        </div>
    @endif

    {{-- Chart.js CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
</div>
