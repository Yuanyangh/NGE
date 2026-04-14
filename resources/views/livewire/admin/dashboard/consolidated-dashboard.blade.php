{{-- ============================================================
     Consolidated Super-Admin Dashboard
     Shows aggregated KPIs across all companies or a single
     company selected from the dropdown.
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

    {{-- ── Control bar ──────────────────────────────────────────────── --}}
    <div class="no-print rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <form wire:submit="regenerate" class="flex flex-col gap-4 lg:flex-row lg:items-end">

            {{-- Company selector --}}
            <div class="w-full lg:w-56">
                <label for="cd-company" class="block text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    Company
                </label>
                <select
                    id="cd-company"
                    wire:model.live="selectedCompanyId"
                    class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                >
                    <option value="">All Companies</option>
                    @foreach ($this->companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Date range --}}
            <div class="flex-1">
                <label for="cd-start" class="block text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    From
                </label>
                <input
                    id="cd-start"
                    type="date"
                    wire:model="startDate"
                    class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                />
                @error('startDate') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex-1">
                <label for="cd-end" class="block text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    To
                </label>
                <input
                    id="cd-end"
                    type="date"
                    wire:model="endDate"
                    class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                />
                @error('endDate') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            {{-- Actions --}}
            <div class="flex shrink-0 items-center gap-3">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    <svg wire:loading wire:target="regenerate" class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <svg wire:loading.remove wire:target="regenerate" class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                    </svg>
                    Update
                </button>
                <button
                    type="button"
                    onclick="window.print()"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2"
                >
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.056 48.056 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"/>
                    </svg>
                    Print
                </button>
            </div>
        </form>
    </div>

    {{-- Loading skeleton --}}
    <div wire:loading wire:target="regenerate,selectedCompanyId" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @for ($i = 0; $i < 8; $i++)
            <div class="h-24 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800"></div>
        @endfor
    </div>

    {{-- ── Main content ─────────────────────────────────────────────── --}}
    <div wire:loading.remove wire:target="regenerate,selectedCompanyId" class="space-y-6">

        @php
            $report = $this->report;
            $isAll  = $report['mode'] === 'all';

            $makeChange = function (string $pct): array {
                $isUp = bccomp($pct, '0', 2) >= 0;
                return [
                    'pct'   => abs((float) $pct),
                    'isUp'  => $isUp,
                    'label' => abs((float) $pct) . '%',
                ];
            };
        @endphp

        @if ($isAll)
            {{-- =========================================================
                 ALL COMPANIES MODE
                 ========================================================= --}}
            @php
                $t  = $report['totals'];
                $vc = $makeChange($t['volume_change']);
                $cc = $makeChange($t['commission_change']);
                $ac = $makeChange($t['affiliate_change']);
                $ec = $makeChange($t['enrollment_change']);
            @endphp

            {{-- Viral cap warning (any company triggered) --}}
            @if ($t['viral_caps'] > 0)
                <div class="flex items-start gap-3 rounded-xl border border-orange-300 bg-orange-50 px-5 py-4 dark:border-orange-700 dark:bg-orange-900/20">
                    <svg class="mt-0.5 size-5 shrink-0 text-orange-500 dark:text-orange-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                    </svg>
                    <div>
                        <p class="font-semibold text-orange-800 dark:text-orange-300">
                            Viral QVV cap triggered {{ $t['viral_caps'] }} time(s) across all companies this period
                        </p>
                        <p class="mt-1 text-sm text-orange-700 dark:text-orange-400">
                            Review individual company KPI dashboards for cap impact details.
                        </p>
                    </div>
                </div>
            @endif

            {{-- Row 1: Primary KPI cards --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-admin.stat-card
                    label="Combined Volume (XP)"
                    :value="number_format((float) $t['volume'], 0)"
                    :trend="$vc['label']"
                    :trend-up="$vc['isUp']"
                    color="indigo"
                />
                <x-admin.stat-card
                    label="Combined Commissions"
                    :value="'$' . number_format((float) $t['commissions'], 2)"
                    :trend="$cc['label']"
                    :trend-up="$cc['isUp']"
                    color="emerald"
                />
                <x-admin.stat-card
                    label="Total Active Affiliates"
                    :value="number_format($t['affiliates']) . ' / ' . number_format($t['total_affiliates'])"
                    :trend="$ac['label']"
                    :trend-up="$ac['isUp']"
                    color="amber"
                />
                <x-admin.stat-card
                    label="Total Enrollments"
                    :value="number_format($t['enrollments'])"
                    :trend="$ec['label']"
                    :trend-up="$ec['isUp']"
                    color="rose"
                />
            </div>

            {{-- Row 2: Secondary KPI cards --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-admin.stat-card
                    label="Combined Payout Ratio"
                    :value="$t['payout_ratio'] . '%'"
                    color="sky"
                />
                <x-admin.stat-card
                    label="Combined Bonuses"
                    :value="'$' . number_format((float) $t['bonuses'], 2)"
                    color="violet"
                />
                <x-admin.stat-card
                    label="Total Commission Runs"
                    :value="number_format($t['run_count'])"
                    color="teal"
                />
                <x-admin.stat-card
                    label="Viral Cap Triggers"
                    :value="number_format($t['viral_caps'])"
                    color="indigo"
                />
            </div>

            {{-- Per-company breakdown table + Global top earners --}}
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

                {{-- Per-company breakdown --}}
                <div class="xl:col-span-2">
                    <x-admin.form-section title="Per-Company Breakdown" description="Click a row to drill into that company's dashboard.">
                        @if (empty($report['perCompany']))
                            <x-admin.empty-state message="No companies found." />
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-slate-100 dark:border-slate-800">
                                            <th class="pb-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Company</th>
                                            <th class="pb-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Volume</th>
                                            <th class="pb-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Commissions</th>
                                            <th class="pb-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Bonuses</th>
                                            <th class="pb-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Affiliates</th>
                                            <th class="pb-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Payout %</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                        @php
                                            $companyColors = [
                                                'bg-indigo-500', 'bg-emerald-500', 'bg-amber-500',
                                                'bg-rose-500', 'bg-violet-500', 'bg-teal-500',
                                                'bg-orange-500', 'bg-sky-500', 'bg-pink-500', 'bg-cyan-500',
                                            ];
                                        @endphp
                                        @foreach ($report['perCompany'] as $idx => $row)
                                            @php
                                                $dotColor = $companyColors[$idx % count($companyColors)];
                                                $kpi = $row['kpi'];
                                            @endphp
                                            <tr
                                                wire:click="$set('selectedCompanyId', {{ $row['company']->id }})"
                                                class="cursor-pointer transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/50"
                                            >
                                                <td class="py-3 pr-4">
                                                    <div class="flex items-center gap-2.5">
                                                        <span class="size-2.5 shrink-0 rounded-full {{ $dotColor }}"></span>
                                                        <span class="font-medium text-slate-900 dark:text-white">{{ $row['company']->name }}</span>
                                                        @if (!$row['company']->is_active)
                                                            <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-500 dark:bg-slate-800 dark:text-slate-400">Inactive</span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="py-3 pr-4 text-right tabular-nums text-slate-700 dark:text-slate-300">
                                                    {{ number_format((float) $kpi->totalVolume, 0) }}
                                                </td>
                                                <td class="py-3 pr-4 text-right tabular-nums text-slate-700 dark:text-slate-300">
                                                    ${{ number_format((float) $kpi->totalCommissions, 2) }}
                                                </td>
                                                <td class="py-3 pr-4 text-right tabular-nums text-slate-700 dark:text-slate-300">
                                                    ${{ number_format((float) $kpi->totalBonuses, 2) }}
                                                </td>
                                                <td class="py-3 pr-4 text-right tabular-nums text-slate-700 dark:text-slate-300">
                                                    {{ number_format($kpi->activeAffiliates) }} / {{ number_format($kpi->totalAffiliates) }}
                                                </td>
                                                <td class="py-3 text-right">
                                                    @php
                                                        $ratio = (float) $kpi->payoutRatio;
                                                        $ratioClass = match(true) {
                                                            $ratio >= 50 => 'text-rose-600 dark:text-rose-400',
                                                            $ratio >= 35 => 'text-amber-600 dark:text-amber-400',
                                                            default      => 'text-emerald-600 dark:text-emerald-400',
                                                        };
                                                    @endphp
                                                    <span class="font-medium tabular-nums {{ $ratioClass }}">
                                                        {{ $kpi->payoutRatio }}%
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <p class="mt-3 text-xs text-slate-400 dark:text-slate-500">Click a row to view that company's full dashboard.</p>
                        @endif
                    </x-admin.form-section>
                </div>

                {{-- Global top 5 earners --}}
                <div>
                    <x-admin.form-section title="Top 5 Global Earners" description="Highest earners across all companies.">
                        @if (empty($report['topEarners']))
                            <x-admin.empty-state message="No earners found for this period." />
                        @else
                            @php $maxGlobal = max(array_column($report['topEarners'], 'total_earnings') + ['1']); @endphp
                            <div class="space-y-4">
                                @foreach ($report['topEarners'] as $i => $earner)
                                    @php
                                        $barPct = (int) round(((float) $earner['total_earnings'] / (float) $maxGlobal) * 100);
                                    @endphp
                                    <div>
                                        <div class="mb-1 flex items-start justify-between gap-2 text-sm">
                                            <div class="min-w-0">
                                                <span class="flex items-center gap-1.5">
                                                    <span class="flex size-5 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-[10px] font-bold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">{{ $i + 1 }}</span>
                                                    <span class="truncate font-medium text-slate-900 dark:text-white">{{ $earner['name'] }}</span>
                                                </span>
                                                <span class="ml-6 mt-0.5 block truncate text-xs text-slate-400 dark:text-slate-500">{{ $earner['company_name'] }}</span>
                                            </div>
                                            <span class="shrink-0 tabular-nums text-slate-700 dark:text-slate-300">${{ number_format((float) $earner['total_earnings'], 2) }}</span>
                                        </div>
                                        <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                            <div class="print-bar h-2 rounded-full bg-indigo-400 dark:bg-indigo-500" style="width: {{ $barPct }}%;"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </x-admin.form-section>
                </div>

            </div>{{-- /grid --}}

        @else
            {{-- =========================================================
                 SINGLE COMPANY MODE
                 ========================================================= --}}
            @php
                $kpi = $report['data'];
                $company = $report['company'];
                $vc = $makeChange($kpi->volumeChange);
                $cc = $makeChange($kpi->commissionChange);
                $ac = $makeChange($kpi->affiliateChange);
                $ec = $makeChange($kpi->enrollmentChange);
            @endphp

            {{-- Company context banner --}}
            <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-5 py-4 dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center gap-3">
                    <div class="flex size-9 items-center justify-center rounded-lg bg-indigo-50 dark:bg-indigo-500/10">
                        <svg class="size-5 text-indigo-600 dark:text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-900 dark:text-white">{{ $company?->name ?? 'Company' }}</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ $kpi->periodStart }} — {{ $kpi->periodEnd }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    @if ($company)
                        <a
                            href="{{ route('admin.companies.dashboard', $company) }}"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                        >
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
                            </svg>
                            Full Dashboard
                        </a>
                    @endif
                    <button
                        type="button"
                        wire:click="$set('selectedCompanyId', null)"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                    >
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                        </svg>
                        All Companies
                    </button>
                </div>
            </div>

            {{-- Viral cap warning for this company --}}
            @if ($kpi->viralCapTriggeredCount > 0)
                <div class="flex items-start gap-3 rounded-xl border border-orange-300 bg-orange-50 px-5 py-4 dark:border-orange-700 dark:bg-orange-900/20">
                    <svg class="mt-0.5 size-5 shrink-0 text-orange-500 dark:text-orange-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                    </svg>
                    <div>
                        <p class="font-semibold text-orange-800 dark:text-orange-300">
                            Viral QVV cap triggered {{ $kpi->viralCapTriggeredCount }} time(s) this period
                        </p>
                        <p class="mt-1 text-sm text-orange-700 dark:text-orange-400">
                            View the full company dashboard for cap impact details.
                        </p>
                    </div>
                </div>
            @endif

            {{-- Primary stat cards --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-admin.stat-card
                    label="Total Volume (XP)"
                    :value="number_format((float) $kpi->totalVolume, 0)"
                    :trend="$vc['label']"
                    :trend-up="$vc['isUp']"
                    color="indigo"
                />
                <x-admin.stat-card
                    label="Total Commissions"
                    :value="'$' . number_format((float) $kpi->totalCommissions, 2)"
                    :trend="$cc['label']"
                    :trend-up="$cc['isUp']"
                    color="emerald"
                />
                <x-admin.stat-card
                    label="Active Affiliates"
                    :value="number_format($kpi->activeAffiliates) . ' / ' . number_format($kpi->totalAffiliates)"
                    :trend="$ac['label']"
                    :trend-up="$ac['isUp']"
                    color="amber"
                />
                <x-admin.stat-card
                    label="New Enrollments"
                    :value="number_format($kpi->newEnrollments)"
                    :trend="$ec['label']"
                    :trend-up="$ec['isUp']"
                    color="rose"
                />
            </div>

            {{-- Secondary stat cards --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-admin.stat-card
                    label="Payout Ratio"
                    :value="$kpi->payoutRatio . '%'"
                    color="sky"
                />
                <x-admin.stat-card
                    label="Total Bonuses"
                    :value="'$' . number_format((float) $kpi->totalBonuses, 2)"
                    color="violet"
                />
                <x-admin.stat-card
                    label="Active Customers"
                    :value="number_format($kpi->activeCustomers)"
                    color="teal"
                />
                <x-admin.stat-card
                    label="Commission Runs"
                    :value="number_format($kpi->commissionRunCount)"
                    color="indigo"
                />
            </div>

            {{-- Top earners for this company --}}
            <x-admin.form-section
                title="Top 5 Earners — {{ $company?->name }}"
                description="Highest total commission + bonus earners for this period."
                class="max-w-xl"
            >
                @if (empty($kpi->topEarners))
                    <x-admin.empty-state message="No earners found for this period." />
                @else
                    @php $maxEarning = max(array_column($kpi->topEarners, 'total_earnings') + ['1']); @endphp
                    <div class="space-y-3">
                        @foreach ($kpi->topEarners as $i => $earner)
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

        @endif

    </div>{{-- end wire:loading.remove --}}
</div>
