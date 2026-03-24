<x-admin-layout title="Commission Run #{{ $commissionRun->id }}">
    <x-admin.page-header title="Commission Run #{{ $commissionRun->id }}" description="Details for the commission run on {{ $commissionRun->run_date?->format('M j, Y') }}.">
        <x-slot:actions>
            <a href="{{ route('admin.commission-runs.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                Back
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="mt-6 space-y-6">
        {{-- Run Details --}}
        <x-admin.form-section title="Run Details">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Company</dt>
                    <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ $commissionRun->company?->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Compensation Plan</dt>
                    <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ $commissionRun->compensationPlan?->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Run Date</dt>
                    <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ $commissionRun->run_date?->format('M j, Y') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Status</dt>
                    <dd class="mt-1">
                        @php
                            $statusColor = match($commissionRun->status) {
                                'completed' => 'success',
                                'running' => 'warning',
                                'failed' => 'danger',
                                default => 'gray',
                            };
                        @endphp
                        <x-admin.badge :color="$statusColor" size="md">{{ $commissionRun->status }}</x-admin.badge>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Started At</dt>
                    <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ $commissionRun->started_at?->format('M j, Y g:i:s A') ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Completed At</dt>
                    <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ $commissionRun->completed_at?->format('M j, Y g:i:s A') ?? '-' }}</dd>
                </div>
            </dl>
        </x-admin.form-section>

        {{-- Financial Summary --}}
        <x-admin.form-section title="Financial Summary">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Affiliate Commission</dt>
                    <dd class="mt-1">
                        <x-admin.money :amount="$commissionRun->total_affiliate_commission" class="text-base" />
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Viral Commission</dt>
                    <dd class="mt-1">
                        <x-admin.money :amount="$commissionRun->total_viral_commission" class="text-base" />
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Company Volume</dt>
                    <dd class="mt-1">
                        <x-admin.money :amount="$commissionRun->total_company_volume" class="text-base" />
                    </dd>
                </div>
            </dl>
        </x-admin.form-section>

        {{-- Flags --}}
        <x-admin.form-section title="Flags">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Viral Cap Triggered</dt>
                    <dd class="mt-1">
                        @if ($commissionRun->viral_cap_triggered)
                            <x-admin.badge color="warning" size="md">Yes</x-admin.badge>
                        @else
                            <x-admin.badge color="gray" size="md">No</x-admin.badge>
                        @endif
                    </dd>
                </div>
                @if ($commissionRun->viral_cap_triggered && $commissionRun->viral_cap_reduction_pct)
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Viral Cap Reduction</dt>
                        <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ number_format((float) $commissionRun->viral_cap_reduction_pct * 100, 2) }}%</dd>
                    </div>
                @endif
                @if ($commissionRun->error_message)
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Error Message</dt>
                        <dd class="mt-1 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700 dark:border-rose-800 dark:bg-rose-900/20 dark:text-rose-400">
                            {{ $commissionRun->error_message }}
                        </dd>
                    </div>
                @endif
            </dl>
        </x-admin.form-section>
    </div>
</x-admin-layout>
