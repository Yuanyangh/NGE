<x-admin-layout title="Dashboard">
    <x-admin.page-header title="Dashboard" description="Platform overview and recent activity." />

    <div class="mt-6 space-y-6">
        {{-- Stats row --}}
        <livewire:admin.dashboard.stats-overview />

        {{-- Two column: Recent Runs (wider) + Chart --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <livewire:admin.dashboard.recent-runs-table />
            </div>
            <div>
                <livewire:admin.dashboard.commission-trend-chart />
            </div>
        </div>
    </div>
</x-admin-layout>
