<x-admin-layout title="Income Disclosure — {{ $company->name }}">
    <x-admin.page-header
        title="Income Disclosure Report"
        description="{{ $company->name }} — auto-generated from commission and bonus ledger data."
    >
        <x-slot:actions>
            <a
                href="{{ route('admin.companies.index') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
            >
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
                Back to Companies
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="mt-6">
        <livewire:admin.reports.income-disclosure-report
            :company-id="$company->id"
            :start-date="$startDate"
            :end-date="$endDate"
        />
    </div>
</x-admin-layout>
