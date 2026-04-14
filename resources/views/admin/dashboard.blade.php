<x-admin-layout title="Dashboard">
    <x-admin.page-header title="Dashboard" description="Platform overview across all companies.">
        @if(auth()->user()?->isSuperAdmin())
            <x-slot:actions>
                <a
                    href="{{ route('admin.companies.index') }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                >
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                    </svg>
                    Manage Companies
                </a>
            </x-slot:actions>
        @endif
    </x-admin.page-header>

    <div class="mt-6">
        @if(auth()->user()?->isSuperAdmin())
            <livewire:admin.dashboard.consolidated-dashboard />
        @else
            {{-- Company admins are redirected by DashboardController before reaching this view --}}
            <div class="space-y-6">
                <livewire:admin.dashboard.stats-overview />
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div class="lg:col-span-2">
                        <livewire:admin.dashboard.recent-runs-table />
                    </div>
                    <div>
                        <livewire:admin.dashboard.commission-trend-chart />
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-admin-layout>
