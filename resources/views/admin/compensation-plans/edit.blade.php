<x-admin-layout title="Edit Compensation Plan">
    <x-admin.page-header title="Edit Compensation Plan" description="Update plan configuration and settings.">
        <x-slot:actions>
            <a href="{{ route('admin.compensation-plans.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                Back
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="mt-6">
        <form method="POST" action="{{ route('admin.compensation-plans.update', $plan->id) }}">
            @csrf
            @method('PUT')

            <x-admin.form-section title="Plan Details" description="Basic plan information and company assignment.">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-admin.select
                        name="company_id"
                        label="Company"
                        :options="$companies"
                        :value="old('company_id', $plan->company_id)"
                        required
                        placeholder="Select a company"
                    />
                    <x-admin.input name="name" label="Plan Name" :value="old('name', $plan->name)" required />
                    <x-admin.input name="version" label="Version" :value="old('version', $plan->version)" required />
                    <div></div>
                    <x-admin.input name="effective_from" label="Effective From" type="date" :value="old('effective_from', $plan->effective_from?->format('Y-m-d'))" required />
                    <x-admin.input name="effective_until" label="Effective Until" type="date" :value="old('effective_until', $plan->effective_until?->format('Y-m-d'))" />
                </div>
                <div class="mt-5">
                    <x-admin.toggle name="is_active" label="Active" :checked="old('is_active', $plan->is_active)" />
                </div>
            </x-admin.form-section>

            <div class="mt-6">
                <x-admin.form-section title="Plan Configuration" description="JSON configuration for the compensation plan engine.">
                    <x-admin.textarea
                        name="config"
                        label="Configuration (JSON)"
                        :rows="20"
                        required
                        :value="old('config', json_encode($plan->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))"
                    />
                    @error('config')
                    @else
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Must be valid JSON. This configuration drives the commission calculation engine.</p>
                    @enderror
                </x-admin.form-section>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <a href="{{ route('admin.compensation-plans.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                    Cancel
                </a>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900">
                    Update Plan
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
