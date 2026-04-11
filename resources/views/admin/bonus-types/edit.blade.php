<x-admin-layout title="Edit Bonus Type">
    <x-admin.page-header
        :title="'Edit: ' . $bonusType->name"
        :description="$plan->name . ' — ' . $company->name"
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
        <form method="POST" action="{{ route('admin.companies.plans.bonus-types.update', [$company, $plan, $bonusType]) }}">
            @csrf
            @method('PUT')

            {{-- Livewire form component pre-populated with existing data --}}
            <livewire:admin.forms.bonus-type-form :company="$company" :plan="$plan" :bonusType="$bonusType" />

            {{-- Submit --}}
            <div class="mt-6 flex items-center justify-end gap-3">
                <a href="{{ route('admin.companies.plans.bonus-types.index', [$company, $plan]) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    Save Changes
                </button>
            </div>
        </form>

        {{-- Delete is a separate form to avoid nesting --}}
        <div class="mt-4 border-t border-slate-200 pt-4 dark:border-slate-800">
            <form method="POST" action="{{ route('admin.companies.plans.bonus-types.destroy', [$company, $plan, $bonusType]) }}" onsubmit="return confirm('Delete this bonus type? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-rose-300 px-4 py-2 text-sm font-medium text-rose-600 transition-colors hover:bg-rose-50 dark:border-rose-700 dark:text-rose-400 dark:hover:bg-rose-900/20">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                    Delete This Bonus Type
                </button>
            </form>
        </div>
    </div>
</x-admin-layout>
