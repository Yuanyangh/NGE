<x-admin-layout title="Commission Runs">
    <x-admin.page-header title="Commission Runs" description="View and trigger commission calculation runs." />

    @if (session('success'))
        <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-400">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-800 dark:bg-rose-900/20 dark:text-rose-400">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="mt-6">
        <livewire:admin.tables.commission-run-table />
    </div>
</x-admin-layout>
