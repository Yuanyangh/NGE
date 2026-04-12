{{-- ============================================================
     Churn Risk Widget
     Calls: ChurnDetector::scan()
     ============================================================ --}}

<div>
    <x-admin.form-section
        title="Churn Risk Detection"
        description="These affiliates show signs of disengagement based on their activity patterns. Early intervention can help retain them."
    >
        {{-- ── Threshold Settings ──────────────────────────────────── --}}
        <div class="mb-5 rounded-lg border border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-700 dark:bg-slate-800/50">
            <p class="mb-3 text-sm font-medium text-slate-700 dark:text-slate-300">Detection Thresholds</p>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {{-- At Risk Days --}}
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">At-Risk: no orders for</label>
                    <div class="mt-1 flex items-center gap-1">
                        <input
                            type="number"
                            wire:model="atRiskDays"
                            min="1"
                            max="365"
                            class="block w-20 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                        />
                        <span class="text-xs text-slate-500 dark:text-slate-400">days</span>
                    </div>
                    @error('atRiskDays')
                        <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Inactive Days --}}
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">Inactive warning: no orders for</label>
                    <div class="mt-1 flex items-center gap-1">
                        <input
                            type="number"
                            wire:model="inactiveDays"
                            min="1"
                            max="365"
                            class="block w-20 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                        />
                        <span class="text-xs text-slate-500 dark:text-slate-400">days</span>
                    </div>
                    @error('inactiveDays')
                        <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Volume Decline % --}}
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">Declining: MoM volume drop</label>
                    <div class="mt-1 flex items-center gap-1">
                        <input
                            type="number"
                            wire:model="volumeDeclinePct"
                            min="1"
                            max="100"
                            class="block w-20 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                        />
                        <span class="text-xs text-slate-500 dark:text-slate-400">%+</span>
                    </div>
                    @error('volumeDeclinePct')
                        <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Stagnant Leader Days --}}
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">Stagnant leader: no downline orders</label>
                    <div class="mt-1 flex items-center gap-1">
                        <input
                            type="number"
                            wire:model="stagnantLeaderDays"
                            min="1"
                            max="365"
                            class="block w-20 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                        />
                        <span class="text-xs text-slate-500 dark:text-slate-400">days</span>
                    </div>
                    @error('stagnantLeaderDays')
                        <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-4 flex items-center gap-3">
                <button
                    wire:click="saveThresholds"
                    wire:loading.attr="disabled"
                    wire:target="saveThresholds"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    <span wire:loading wire:target="saveThresholds">
                        <svg class="size-3.5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </span>
                    Save Thresholds
                </button>

                @if ($thresholdsSaved)
                    <span class="flex items-center gap-1.5 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                        <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                        </svg>
                        Saved. Reload the page to re-scan with updated thresholds.
                    </span>
                @endif
            </div>
        </div>

        {{-- ── Loading state ───────────────────────────────────────── --}}
        <div wire:loading class="flex items-center gap-2 py-4 text-sm text-slate-500 dark:text-slate-400">
            <svg class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            Scanning affiliates...
        </div>

        <div wire:loading.remove>
            @if ($this->churnResults->isEmpty())
                {{-- All clear --}}
                <div class="flex flex-col items-center justify-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 py-10 text-center dark:border-emerald-800/40 dark:bg-emerald-900/10">
                    <div class="flex size-12 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30">
                        <svg class="size-6 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-base font-semibold text-emerald-800 dark:text-emerald-300">No Churn Risk Detected</p>
                        <p class="mt-1 text-sm text-emerald-700 dark:text-emerald-400">
                            All active affiliates are within normal activity thresholds.
                        </p>
                    </div>
                </div>
            @else
                {{-- ── Summary cards ─────────────────────────────────── --}}
                <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    {{-- Inactive Warning --}}
                    <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-800/40 dark:bg-rose-900/10">
                        <p class="text-2xl font-bold tabular-nums text-rose-700 dark:text-rose-400">
                            {{ $this->summary['inactive_warning'] }}
                        </p>
                        <p class="mt-1 text-xs font-medium text-rose-800 dark:text-rose-300">Inactive Warning</p>
                        <p class="text-xs text-rose-600 dark:text-rose-500">No orders {{ $inactiveDays }}+ days</p>
                    </div>

                    {{-- At Risk --}}
                    <div class="rounded-xl border border-orange-200 bg-orange-50 p-4 dark:border-orange-800/40 dark:bg-orange-900/10">
                        <p class="text-2xl font-bold tabular-nums text-orange-700 dark:text-orange-400">
                            {{ $this->summary['at_risk'] }}
                        </p>
                        <p class="mt-1 text-xs font-medium text-orange-800 dark:text-orange-300">At Risk</p>
                        <p class="text-xs text-orange-600 dark:text-orange-500">No orders {{ $atRiskDays }}+ days</p>
                    </div>

                    {{-- Declining --}}
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800/40 dark:bg-amber-900/10">
                        <p class="text-2xl font-bold tabular-nums text-amber-700 dark:text-amber-400">
                            {{ $this->summary['declining'] }}
                        </p>
                        <p class="mt-1 text-xs font-medium text-amber-800 dark:text-amber-300">Declining</p>
                        <p class="text-xs text-amber-600 dark:text-amber-500">Volume down {{ $volumeDeclinePct }}%+</p>
                    </div>

                    {{-- Stagnant Leader --}}
                    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-800/40 dark:bg-blue-900/10">
                        <p class="text-2xl font-bold tabular-nums text-blue-700 dark:text-blue-400">
                            {{ $this->summary['stagnant_leader'] }}
                        </p>
                        <p class="mt-1 text-xs font-medium text-blue-800 dark:text-blue-300">Stagnant Leader</p>
                        <p class="text-xs text-blue-600 dark:text-blue-500">No downline orders {{ $stagnantLeaderDays }}+ days</p>
                    </div>
                </div>

                {{-- ── Affiliate table ────────────────────────────────── --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th class="pb-3 pr-6 text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Affiliate</th>
                                <th class="pb-3 pr-6 text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Risk Level</th>
                                <th class="pb-3 pr-6 text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Reason</th>
                                <th class="pb-3 pr-6 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Days Inactive</th>
                                <th class="pb-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Vol. Change</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @php
                                $riskOrder = ['inactive_warning' => 0, 'at_risk' => 1, 'declining' => 2, 'stagnant_leader' => 3];
                                $sorted = $this->churnResults->sortBy(fn ($r) => $riskOrder[$r->risk_level] ?? 99);
                            @endphp
                            @foreach ($sorted as $result)
                                @php
                                    $badgeClass = match($result->risk_level) {
                                        'inactive_warning' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300',
                                        'at_risk'          => 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300',
                                        'declining'        => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                                        'stagnant_leader'  => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                                        default            => 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300',
                                    };
                                    $rowHighlight = $result->risk_level === 'inactive_warning'
                                        ? 'bg-rose-50/40 dark:bg-rose-900/5'
                                        : '';
                                    $badgeLabel = match($result->risk_level) {
                                        'inactive_warning' => 'Inactive',
                                        'at_risk'          => 'At Risk',
                                        'declining'        => 'Declining',
                                        'stagnant_leader'  => 'Stagnant Leader',
                                        default            => $result->risk_level,
                                    };
                                @endphp
                                <tr class="{{ $rowHighlight }}">
                                    <td class="py-3 pr-6">
                                        <span class="font-medium text-slate-900 dark:text-white">{{ $result->user_name }}</span>
                                        <span class="ml-2 text-xs text-slate-400 dark:text-slate-500">#{{ $result->user_id }}</span>
                                    </td>
                                    <td class="py-3 pr-6">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badgeClass }}">
                                            {{ $badgeLabel }}
                                        </span>
                                    </td>
                                    <td class="py-3 pr-6 text-slate-600 dark:text-slate-400">
                                        {{ $result->reason }}
                                    </td>
                                    <td class="py-3 pr-6 text-right tabular-nums text-slate-700 dark:text-slate-300">
                                        @if ($result->days_since_last_order !== null)
                                            {{ $result->days_since_last_order }}d
                                        @else
                                            <span class="text-slate-400 dark:text-slate-600">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3 text-right tabular-nums">
                                        @if ($result->volume_change_pct !== null)
                                            <span class="font-medium text-rose-600 dark:text-rose-400">
                                                {{ $result->volume_change_pct }}%
                                            </span>
                                        @else
                                            <span class="text-slate-400 dark:text-slate-600">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">
                    Scan as of {{ \Carbon\Carbon::parse($scanDate)->format('M j, Y') }}.
                    {{ $this->summary['total'] }} affiliate(s) flagged across all risk categories.
                </p>
            @endif
        </div>
    </x-admin.form-section>
</div>
