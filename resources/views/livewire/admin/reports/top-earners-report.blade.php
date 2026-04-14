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

    <div wire:loading wire:target="regenerate" class="h-64 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800"></div>

    <div wire:loading.remove wire:target="regenerate">
        <x-admin.form-section title="Top Earners" :description="'Top ' . $limit . ' affiliates by total earnings for the selected period.'">

            <div class="no-print mb-4 flex items-center gap-2">
                <label class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Show top</label>
                <select wire:model.live="limit" class="rounded-lg border border-slate-300 bg-white px-2 py-1 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>

            @if ($this->earners->isEmpty())
                <x-admin.empty-state message="No earners found for this period." />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th class="pb-3 pr-4 text-xs font-medium uppercase tracking-wider text-slate-500">#</th>
                                <th class="pb-3 pr-4 text-xs font-medium uppercase tracking-wider text-slate-500">Affiliate</th>
                                <th class="pb-3 pr-4 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Commissions</th>
                                <th class="pb-3 pr-4 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Bonuses</th>
                                <th class="pb-3 pr-4 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Total</th>
                                <th class="pb-3 text-xs font-medium uppercase tracking-wider text-slate-500">Proportion</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($this->earners as $i => $earner)
                                <tr class="{{ $i % 2 === 0 ? '' : 'bg-slate-50/50 dark:bg-slate-800/30' }} hover:bg-indigo-50/50 dark:hover:bg-indigo-900/10">
                                    <td class="py-3 pr-4 text-slate-400">{{ $i + 1 }}</td>
                                    <td class="py-3 pr-4 font-medium text-slate-900 dark:text-white">{{ $earner['name'] }}</td>
                                    <td class="py-3 pr-4 text-right tabular-nums text-emerald-700 dark:text-emerald-400">${{ number_format((float) $earner['comm'], 2) }}</td>
                                    <td class="py-3 pr-4 text-right tabular-nums text-amber-700 dark:text-amber-400">${{ number_format((float) $earner['bonus'], 2) }}</td>
                                    <td class="py-3 pr-4 text-right tabular-nums font-bold text-slate-900 dark:text-white">${{ number_format((float) $earner['total'], 2) }}</td>
                                    <td class="py-3 w-full min-w-[8rem]">
                                        <div class="h-3 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                            <div class="print-bar h-3 rounded-full bg-indigo-400 dark:bg-indigo-500"
                                                style="width: {{ $earner['bar_width'] }}%;"
                                                role="progressbar"
                                                aria-valuenow="{{ $earner['bar_width'] }}"
                                                aria-valuemin="0"
                                                aria-valuemax="100"
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
    </div>
</div>
