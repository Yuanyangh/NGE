<x-filament-panels::page>
    <form wire:submit="runSimulation">
        {{ $this->form }}

        <div class="mt-4 flex gap-3">
            <x-filament::button type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="runSimulation">Run Simulation</span>
                <span wire:loading wire:target="runSimulation">Running...</span>
            </x-filament::button>
        </div>
    </form>

    @if($results)
        <div class="mt-8 space-y-6">
            {{-- Export & Compare Controls --}}
            <div class="flex flex-wrap gap-3 items-end">
                <x-filament::button color="gray" wire:click="exportCsv">
                    Export CSV
                </x-filament::button>

                <div class="flex items-end gap-2">
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Compare with</label>
                        <select wire:model="compare_run_id" class="block mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                            <option value="">-- Select past run --</option>
                            @foreach($this->savedRuns as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-filament::button color="gray" size="sm" wire:click="loadPastRun">Load</x-filament::button>
                    @if($compareResults)
                        <x-filament::button color="danger" size="sm" wire:click="clearComparison">Clear</x-filament::button>
                    @endif
                </div>
            </div>

            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <x-filament::section>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Payout</div>
                    <div class="text-2xl font-bold">${{ number_format((float) $results['summary']['total_payout'], 2) }}</div>
                    @if($compareResults)
                        <div class="text-xs text-gray-400 mt-1">vs ${{ number_format((float) $compareResults['summary']['total_payout'], 2) }}</div>
                    @endif
                </x-filament::section>

                <x-filament::section>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Payout Ratio</div>
                    <div class="text-2xl font-bold">{{ number_format($results['summary']['payout_ratio_percent'], 1) }}%</div>
                    @if($compareResults)
                        <div class="text-xs text-gray-400 mt-1">vs {{ number_format($compareResults['summary']['payout_ratio_percent'], 1) }}%</div>
                    @endif
                </x-filament::section>

                <x-filament::section>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Cap Trigger Days</div>
                    <div class="text-2xl font-bold">
                        {{ $results['summary']['viral_cap_triggered_days'] }} viral /
                        {{ $results['summary']['global_cap_triggered_days'] }} global
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Sustainability Score</div>
                    <div class="text-2xl font-bold
                        @if($results['risk_indicators']['sustainability_score'] >= 70) text-green-600
                        @elseif($results['risk_indicators']['sustainability_score'] >= 40) text-yellow-600
                        @else text-red-600
                        @endif">
                        {{ $results['risk_indicators']['sustainability_score'] }} / 100
                    </div>
                    @if($compareResults)
                        <div class="text-xs text-gray-400 mt-1">vs {{ $compareResults['risk_indicators']['sustainability_score'] }} / 100</div>
                    @endif
                </x-filament::section>
            </div>

            {{-- Additional Summary --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-filament::section>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Projected Volume</div>
                    <div class="text-xl font-semibold">${{ number_format((float) $results['summary']['total_projected_volume'], 2) }}</div>
                </x-filament::section>
                <x-filament::section>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Avg Affiliate Earning / Day</div>
                    <div class="text-xl font-semibold">${{ number_format($results['summary']['average_affiliate_earning_per_day'], 2) }}</div>
                </x-filament::section>
                <x-filament::section>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Top Earner Concentration</div>
                    <div class="text-xl font-semibold">{{ number_format($results['summary']['top_earner_concentration_percent'], 1) }}%</div>
                </x-filament::section>
            </div>

            {{-- Risk Indicators --}}
            <x-filament::section heading="Risk Indicators">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Payout Trend</div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            @if($results['risk_indicators']['payout_ratio_trend'] === 'stable') bg-green-100 text-green-800
                            @elseif($results['risk_indicators']['payout_ratio_trend'] === 'decreasing') bg-green-100 text-green-800
                            @else bg-red-100 text-red-800
                            @endif">
                            {{ ucfirst($results['risk_indicators']['payout_ratio_trend']) }}
                        </span>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Cap Frequency</div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            @if(in_array($results['risk_indicators']['cap_trigger_frequency'], ['none', 'rare'])) bg-green-100 text-green-800
                            @elseif($results['risk_indicators']['cap_trigger_frequency'] === 'occasional') bg-yellow-100 text-yellow-800
                            @else bg-red-100 text-red-800
                            @endif">
                            {{ ucfirst($results['risk_indicators']['cap_trigger_frequency']) }}
                        </span>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Top Earner Concentration</div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            @if($results['risk_indicators']['top_earner_concentration'] === 'low') bg-green-100 text-green-800
                            @elseif($results['risk_indicators']['top_earner_concentration'] === 'moderate') bg-yellow-100 text-yellow-800
                            @else bg-red-100 text-red-800
                            @endif">
                            {{ ucfirst($results['risk_indicators']['top_earner_concentration']) }}
                        </span>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Sustainability</div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            @if($results['risk_indicators']['sustainability_score'] >= 70) bg-green-100 text-green-800
                            @elseif($results['risk_indicators']['sustainability_score'] >= 40) bg-yellow-100 text-yellow-800
                            @else bg-red-100 text-red-800
                            @endif">
                            {{ $results['risk_indicators']['sustainability_score'] }} / 100
                        </span>
                    </div>
                </div>
            </x-filament::section>

            {{-- Payout Ratio Chart (with optional comparison overlay) --}}
            <x-filament::section heading="Daily Payout Ratio (%)">
                <div
                    x-data="{
                        init() {
                            const projections = @js($results['daily_projections']);
                            const compare = @js($compareResults ? $compareResults['daily_projections'] : null);
                            const labels = projections.map(p => 'Day ' + p.day);
                            const datasets = [{
                                label: 'Current',
                                data: projections.map(p => parseFloat(p.payout_ratio_percent)),
                                borderColor: '#f59e0b',
                                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                                fill: true,
                                tension: 0.3,
                                pointRadius: 0,
                            }];

                            if (compare) {
                                datasets.push({
                                    label: 'Comparison',
                                    data: compare.map(p => parseFloat(p.payout_ratio_percent)),
                                    borderColor: '#94a3b8',
                                    backgroundColor: 'rgba(148, 163, 184, 0.1)',
                                    fill: false,
                                    borderDash: [5, 5],
                                    tension: 0.3,
                                    pointRadius: 0,
                                });
                            }

                            new Chart(this.$refs.ratioChart, {
                                type: 'line',
                                data: { labels, datasets },
                                options: {
                                    responsive: true,
                                    plugins: { legend: { display: compare !== null } },
                                    scales: {
                                        y: { beginAtZero: true, title: { display: true, text: '%' } },
                                        x: { ticks: { maxTicksLimit: 10 } }
                                    }
                                }
                            });
                        }
                    }"
                >
                    <canvas x-ref="ratioChart" height="80"></canvas>
                </div>
            </x-filament::section>

            {{-- Commissions Stacked Area Chart --}}
            <x-filament::section heading="Daily Commissions (Affiliate vs Viral)">
                <div
                    x-data="{
                        init() {
                            const projections = @js($results['daily_projections']);
                            const labels = projections.map(p => 'Day ' + p.day);

                            new Chart(this.$refs.commissionsChart, {
                                type: 'line',
                                data: {
                                    labels: labels,
                                    datasets: [
                                        {
                                            label: 'Affiliate',
                                            data: projections.map(p => parseFloat(p.affiliate_commissions)),
                                            borderColor: '#3b82f6',
                                            backgroundColor: 'rgba(59, 130, 246, 0.3)',
                                            fill: true,
                                            tension: 0.3,
                                            pointRadius: 0,
                                        },
                                        {
                                            label: 'Viral',
                                            data: projections.map(p => parseFloat(p.viral_commissions)),
                                            borderColor: '#10b981',
                                            backgroundColor: 'rgba(16, 185, 129, 0.3)',
                                            fill: true,
                                            tension: 0.3,
                                            pointRadius: 0,
                                        }
                                    ]
                                },
                                options: {
                                    responsive: true,
                                    scales: {
                                        y: { stacked: true, beginAtZero: true, title: { display: true, text: '$' } },
                                        x: { ticks: { maxTicksLimit: 10 } }
                                    }
                                }
                            });
                        }
                    }"
                >
                    <canvas x-ref="commissionsChart" height="80"></canvas>
                </div>
            </x-filament::section>

            {{-- Tier Distribution Tables --}}
            @if(!empty($results['payout_breakdown']['affiliate_tier_distribution']))
            <x-filament::section heading="Affiliate Tier Distribution">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b dark:border-gray-700">
                            <th class="text-left py-2">Tier Rate</th>
                            <th class="text-right py-2">Affiliate-Days</th>
                            <th class="text-right py-2">Total Paid</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results['payout_breakdown']['affiliate_tier_distribution'] as $tier)
                        <tr class="border-b dark:border-gray-700">
                            <td class="py-2">{{ number_format($tier['tier_rate'] * 100, 0) }}%</td>
                            <td class="text-right py-2">{{ number_format($tier['affiliate_count']) }}</td>
                            <td class="text-right py-2">${{ number_format((float) $tier['total_paid'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-filament::section>
            @endif

            @if(!empty($results['payout_breakdown']['viral_tier_distribution']))
            <x-filament::section heading="Viral Tier Distribution">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b dark:border-gray-700">
                            <th class="text-left py-2">Tier</th>
                            <th class="text-right py-2">Affiliate-Days</th>
                            <th class="text-right py-2">Total Paid</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results['payout_breakdown']['viral_tier_distribution'] as $tier)
                        <tr class="border-b dark:border-gray-700">
                            <td class="py-2">Tier {{ $tier['tier'] }}</td>
                            <td class="text-right py-2">{{ number_format($tier['affiliate_count']) }}</td>
                            <td class="text-right py-2">${{ number_format((float) $tier['total_paid'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-filament::section>
            @endif
        </div>
    @endif

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    @endpush
</x-filament-panels::page>
