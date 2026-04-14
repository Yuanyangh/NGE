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
        <div class="h-20 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800"></div>
        <div class="h-48 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800"></div>
    </div>

    <div wire:loading.remove wire:target="regenerate" class="space-y-6">

        <x-admin.stat-card label="New Enrollments" :value="number_format($this->enrollments->count())" color="violet" />

        {{-- Daily trend --}}
        @if ($this->dailyTrend->isNotEmpty())
            <x-admin.form-section title="Enrollment Trend" description="New affiliates per day during the selected period.">
                @php $maxCount = $this->dailyTrend->max(fn ($r) => $r['count']) ?: 1; @endphp
                <div class="flex h-36 items-end gap-0.5 overflow-x-auto">
                    @foreach ($this->dailyTrend as $point)
                        @php $pct = max(1, (int) round(($point['count'] / $maxCount) * 100)); @endphp
                        <div class="group relative flex-1 min-w-[6px]" title="{{ $point['date'] }}: {{ $point['count'] }} enrollments">
                            <div class="print-bar w-full rounded-t bg-violet-400 transition-colors group-hover:bg-violet-600 dark:bg-violet-500" style="height: {{ $pct }}%;"></div>
                        </div>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ $this->dailyTrend->count() }} days with enrollments shown.</p>
            </x-admin.form-section>
        @endif

        {{-- Enrollments table --}}
        <x-admin.form-section title="New Affiliates" description="All affiliates who enrolled during the selected period.">
            @if ($this->enrollments->isEmpty())
                <x-admin.empty-state message="No new enrollments in this period." />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th class="pb-3 pr-4 text-xs font-medium uppercase tracking-wider text-slate-500">Name</th>
                                <th class="pb-3 pr-4 text-xs font-medium uppercase tracking-wider text-slate-500">Status</th>
                                <th class="pb-3 text-xs font-medium uppercase tracking-wider text-slate-500">Enrolled</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($this->enrollments as $i => $enrollment)
                                <tr class="{{ $i % 2 === 0 ? '' : 'bg-slate-50/50 dark:bg-slate-800/30' }}">
                                    <td class="py-3 pr-4 font-medium text-slate-900 dark:text-white">{{ $enrollment['name'] }}</td>
                                    <td class="py-3 pr-4">
                                        <span class="rounded-full px-2.5 py-0.5 text-xs font-medium
                                            {{ $enrollment['status'] === 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400' }}">
                                            {{ ucfirst($enrollment['status']) }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-slate-600 dark:text-slate-400">
                                        {{ $enrollment['enrolled_at'] ? \Carbon\Carbon::parse($enrollment['enrolled_at'])->format('M j, Y g:i A') : '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-admin.form-section>

    </div>
</div>
