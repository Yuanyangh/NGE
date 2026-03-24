<x-admin-layout title="Compensation Plans">
    <x-admin.page-header title="Compensation Plans" description="Manage compensation plans across all companies.">
        <x-slot:actions>
            <a href="{{ route('admin.compensation-plans.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Create Plan
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('success'))
        <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-400">
            {{ session('success') }}
        </div>
    @endif

    <div class="mt-6">
        <livewire:admin.tables.compensation-plan-table />
    </div>
</x-admin-layout>
