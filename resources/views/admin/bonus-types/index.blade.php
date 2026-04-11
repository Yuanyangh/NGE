<x-admin-layout title="Bonus Types">
    <x-admin.page-header
        title="Bonus Types"
        :description="'Optional bonus programs for ' . $company->name . ' — ' . $plan->name"
    >
        <x-slot:actions>
            <a href="{{ route('admin.compensation-plans.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                Back to Plans
            </a>
            <a href="{{ route('admin.companies.plans.bonus-types.create', [$company, $plan]) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add Bonus Type
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Context breadcrumb --}}
    <div class="mt-4 flex flex-wrap items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
        <span>Company:</span>
        <span class="rounded-md bg-slate-100 px-2 py-0.5 font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $company->name }}</span>
        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
        <span>Plan:</span>
        <span class="rounded-md bg-slate-100 px-2 py-0.5 font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $plan->name }}</span>
        <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs dark:bg-slate-800">v{{ $plan->version }}</code>
    </div>

    @if (session('success'))
        <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-400">
            {{ session('success') }}
        </div>
    @endif

    {{-- Info callout --}}
    <div class="mt-4 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-700 dark:border-sky-800 dark:bg-sky-900/20 dark:text-sky-300">
        <strong>Bonus types are optional.</strong> If none are configured, only the standard affiliate and viral commissions from the compensation plan will run.
        Bonus types let you layer on additional rewards like matching bonuses, fast start incentives, rank advancement payouts, leadership bonuses, and pool sharing.
    </div>

    <div class="mt-6">
        <livewire:admin.tables.bonus-type-table :company="$company" :plan="$plan" />
    </div>
</x-admin-layout>
