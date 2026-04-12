{{-- ============================================================
     Inventory Loading Widget
     Calls: InventoryLoadingDetector::scan()
     ============================================================ --}}

<div>
    {{-- ── Header ─────────────────────────────────────────────────── --}}
    <x-admin.form-section
        title="Anti-Inventory Loading"
        description="These affiliates are buying significantly more product than they are selling to customers. A high ratio may indicate inventory loading."
    >
        {{-- ── Threshold Setting ───────────────────────────────────── --}}
        <div class="mb-5 rounded-lg border border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-700 dark:bg-slate-800/50">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Flagging Threshold</p>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                        Affiliates whose personal purchases represent more than this percentage of their total volume (personal + referred) will be flagged. Affiliates above 95% are marked Critical.
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <div class="relative">
                        <input
                            type="number"
                            wire:model="threshold"
                            step="0.01"
                            min="0.01"
                            max="0.99"
                            class="block w-24 rounded-lg border border-slate-300 bg-white px-3 py-2 pr-7 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                            placeholder="0.80"
                        />
                        <span class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center text-xs text-slate-400">
                            (0–1)
                        </span>
                    </div>
                    <button
                        wire:click="saveThreshold"
                        wire:loading.attr="disabled"
                        wire:target="saveThreshold"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        <span wire:loading wire:target="saveThreshold">
                            <svg class="size-3.5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </span>
                        Save
                    </button>
                </div>
            </div>

            @error('threshold')
                <p class="mt-2 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror

            @if ($thresholdSaved)
                <p class="mt-2 flex items-center gap-1.5 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                    <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                    </svg>
                    Threshold saved. Scan results below use the updated value on next page load.
                </p>
            @endif
        </div>

        {{-- ── Scan results ────────────────────────────────────────── --}}
        <div wire:loading class="flex items-center gap-2 py-4 text-sm text-slate-500 dark:text-slate-400">
            <svg class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            Scanning affiliates...
        </div>

        <div wire:loading.remove>
            @if ($this->flaggedAffiliates->isEmpty())
                {{-- All clear state --}}
                <div class="flex flex-col items-center justify-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 py-10 text-center dark:border-emerald-800/40 dark:bg-emerald-900/10">
                    <div class="flex size-12 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30">
                        <svg class="size-6 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-base font-semibold text-emerald-800 dark:text-emerald-300">All Clear</p>
                        <p class="mt-1 text-sm text-emerald-700 dark:text-emerald-400">
                            No affiliates exceeded the {{ number_format((float) $threshold * 100, 0) }}% personal-purchase threshold in the last 30 days.
                        </p>
                    </div>
                </div>
            @else
                {{-- Summary count --}}
                <div class="mb-4 flex flex-wrap gap-3">
                    @php
                        $criticalCount = $this->flaggedAffiliates->where('risk_level', 'critical')->count();
                        $warningCount  = $this->flaggedAffiliates->where('risk_level', 'warning')->count();
                    @endphp
                    @if ($criticalCount > 0)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-100 px-3 py-1 text-sm font-semibold text-rose-800 dark:bg-rose-900/30 dark:text-rose-300">
                            <span class="size-2 rounded-full bg-rose-500"></span>
                            {{ $criticalCount }} Critical
                        </span>
                    @endif
                    @if ($warningCount > 0)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                            <span class="size-2 rounded-full bg-amber-500"></span>
                            {{ $warningCount }} Warning
                        </span>
                    @endif
                </div>

                {{-- Flagged affiliates table --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th class="pb-3 pr-6 text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Affiliate</th>
                                <th class="pb-3 pr-6 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Personal Vol.</th>
                                <th class="pb-3 pr-6 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Referred Vol.</th>
                                <th class="pb-3 pr-6 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Ratio</th>
                                <th class="pb-3 text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Risk Level</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($this->flaggedAffiliates->sortByDesc('ratio') as $result)
                                @php
                                    $isCritical = $result->risk_level === 'critical';
                                    $ratioPct   = number_format((float) $result->ratio * 100, 1);
                                @endphp
                                <tr class="{{ $isCritical ? 'bg-rose-50/40 dark:bg-rose-900/5' : '' }}">
                                    <td class="py-3 pr-6">
                                        <span class="font-medium text-slate-900 dark:text-white">{{ $result->user_name }}</span>
                                        <span class="ml-2 text-xs text-slate-400 dark:text-slate-500">#{{ $result->user_id }}</span>
                                    </td>
                                    <td class="py-3 pr-6 text-right tabular-nums text-slate-700 dark:text-slate-300">
                                        {{ number_format((float) $result->personal_volume, 2) }}
                                    </td>
                                    <td class="py-3 pr-6 text-right tabular-nums text-slate-700 dark:text-slate-300">
                                        {{ number_format((float) $result->referred_volume, 2) }}
                                    </td>
                                    <td class="py-3 pr-6 text-right tabular-nums font-semibold {{ $isCritical ? 'text-rose-700 dark:text-rose-400' : 'text-amber-700 dark:text-amber-400' }}">
                                        {{ $ratioPct }}%
                                    </td>
                                    <td class="py-3">
                                        @if ($isCritical)
                                            <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                                                Critical
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                                Warning
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">
                    Scan window: last 30 days as of {{ \Carbon\Carbon::parse($scanDate)->format('M j, Y') }}.
                    Threshold applied: {{ number_format((float) $threshold * 100, 0) }}%.
                    Affiliates with no activity are excluded.
                </p>
            @endif
        </div>
    </x-admin.form-section>
</div>
