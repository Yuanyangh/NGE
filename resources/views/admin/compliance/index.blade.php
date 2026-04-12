<x-admin-layout title="Compliance — {{ $company->name }}">
    <x-admin.page-header
        title="Compliance Dashboard"
        description="{{ $company->name }} — anti-inventory loading and churn risk analysis."
    >
        <x-slot:actions>
            <a
                href="{{ route('admin.companies.edit', $company) }}"
                class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
            >
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
                Back to Company
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="mt-6 space-y-8">
        {{-- Inventory Loading Widget --}}
        <livewire:admin.compliance.inventory-loading-widget
            :company-id="$company->id"
            :scan-date="$scanDate"
        />

        {{-- Churn Risk Widget --}}
        <livewire:admin.compliance.churn-risk-widget
            :company-id="$company->id"
            :scan-date="$scanDate"
        />
    </div>
</x-admin-layout>
