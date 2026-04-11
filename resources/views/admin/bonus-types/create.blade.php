<x-admin-layout title="Add Bonus Type">
    <x-admin.page-header
        title="Add Bonus Type"
        :description="'Adding a bonus to: ' . $plan->name . ' (' . $company->name . ')'"
    >
        <x-slot:actions>
            <a href="{{ route('admin.companies.plans.bonus-types.index', [$company, $plan]) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                Back
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($errors->any())
        <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 dark:border-rose-800 dark:bg-rose-900/20">
            <p class="mb-2 text-sm font-semibold text-rose-700 dark:text-rose-400">Please fix the following errors before saving:</p>
            <ul class="list-inside list-disc space-y-0.5 text-sm text-rose-600 dark:text-rose-400">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-6">
        <form method="POST" action="{{ route('admin.companies.plans.bonus-types.store', [$company, $plan]) }}">
            @csrf

            {{-- Livewire form component handles field rendering dynamically --}}
            <livewire:admin.forms.bonus-type-form :company="$company" :plan="$plan" />

            {{-- Submit --}}
            <div class="mt-6 flex items-center justify-end gap-3">
                <a href="{{ route('admin.companies.plans.bonus-types.index', [$company, $plan]) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    Save Bonus Type
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
