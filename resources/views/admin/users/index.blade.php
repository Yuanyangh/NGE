<x-admin-layout title="Users">
    <x-admin.page-header title="Users" description="All users across all companies." />

    @if (session('success'))
        <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-400">
            {{ session('success') }}
        </div>
    @endif

    <div class="mt-6">
        <livewire:admin.tables.user-table />
    </div>
</x-admin-layout>
