<x-admin-layout title="{{ $reportMeta['title'] }} — {{ $company->name }}">
    <x-admin.page-header
        title="{{ $reportMeta['title'] }}"
        description="{{ $company->name }} &middot; {{ $reportMeta['description'] }}"
    >
        <x-slot:actions>
            <a
                href="{{ route('admin.companies.reports.index', $company) }}"
                class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
            >
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
                All Reports
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="mt-6">
        @switch($report)
            @case('commission-summary')
                <livewire:admin.reports.commission-summary-report
                    :company-id="$company->id"
                    :start-date="$startDate"
                    :end-date="$endDate"
                />
                @break
            @case('top-earners')
                <livewire:admin.reports.top-earners-report
                    :company-id="$company->id"
                    :start-date="$startDate"
                    :end-date="$endDate"
                />
                @break
            @case('volume')
                <livewire:admin.reports.volume-report
                    :company-id="$company->id"
                    :start-date="$startDate"
                    :end-date="$endDate"
                />
                @break
            @case('affiliate-activity')
                <livewire:admin.reports.affiliate-activity-report
                    :company-id="$company->id"
                    :start-date="$startDate"
                    :end-date="$endDate"
                />
                @break
            @case('enrollments')
                <livewire:admin.reports.enrollments-report
                    :company-id="$company->id"
                    :start-date="$startDate"
                    :end-date="$endDate"
                />
                @break
            @case('bonus-payout')
                <livewire:admin.reports.bonus-payout-report
                    :company-id="$company->id"
                    :start-date="$startDate"
                    :end-date="$endDate"
                />
                @break
            @case('cap-impact')
                <livewire:admin.reports.cap-impact-report
                    :company-id="$company->id"
                    :start-date="$startDate"
                    :end-date="$endDate"
                />
                @break
            @case('breakage')
                <livewire:admin.reports.breakage-report
                    :company-id="$company->id"
                    :start-date="$startDate"
                    :end-date="$endDate"
                />
                @break
            @case('wallet-movement')
                <livewire:admin.reports.wallet-movement-report
                    :company-id="$company->id"
                    :start-date="$startDate"
                    :end-date="$endDate"
                />
                @break
            @case('churn-risk')
                <livewire:admin.reports.churn-risk-report
                    :company-id="$company->id"
                    :start-date="$startDate"
                    :end-date="$endDate"
                />
                @break
        @endswitch
    </div>
</x-admin-layout>
