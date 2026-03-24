<x-admin-layout title="Create Company">
    <x-admin.page-header title="Create Company" description="Add a new company to the platform.">
        <x-slot:actions>
            <a href="{{ route('admin.companies.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                Back
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="mt-6">
        <form method="POST" action="{{ route('admin.companies.store') }}">
            @csrf

            <x-admin.form-section title="Company Details" description="Basic information about the company.">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-admin.input name="name" label="Company Name" :value="old('name')" required placeholder="Acme Corp" />
                    <x-admin.input name="slug" label="Slug" :value="old('slug')" required placeholder="acme-corp" />
                    <x-admin.input name="timezone" label="Timezone" :value="old('timezone', 'UTC')" placeholder="UTC" />
                    <x-admin.input name="currency" label="Currency" :value="old('currency', 'USD')" placeholder="USD" />
                </div>
                <div class="mt-5">
                    <x-admin.toggle name="is_active" label="Active" :checked="old('is_active', true)" />
                </div>
            </x-admin.form-section>

            <div class="mt-6 flex items-center justify-end gap-3">
                <a href="{{ route('admin.companies.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                    Cancel
                </a>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900">
                    Create Company
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
