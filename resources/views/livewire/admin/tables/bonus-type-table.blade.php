<div>
    {{-- Filters --}}
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Showing bonus types for <span class="font-medium text-slate-700 dark:text-slate-300">{{ $plan->name }}</span>
        </p>
        <select
            wire:model.live="filterActive"
            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
        >
            <option value="">All Status</option>
            <option value="1">Active</option>
            <option value="0">Inactive</option>
        </select>
    </div>

    @if ($bonusTypes->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col items-center justify-center px-6 py-16">
                <div class="flex size-14 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                    <svg class="size-7 text-slate-400 dark:text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <h3 class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">No bonus types configured</h3>
                <p class="mt-1 text-center text-sm text-slate-500 dark:text-slate-400">
                    Bonus types are optional. Add one to configure matching, fast start, rank advancement, pool sharing, or leadership bonuses.
                </p>
                <div class="mt-4">
                    <a href="{{ route('admin.companies.plans.bonus-types.create', [$company, $plan]) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-500">
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Add First Bonus Type
                    </a>
                </div>
            </div>
        </div>
    @else
        <x-admin.data-table>
            <x-slot:header>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Name</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Type</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    <button wire:click="sortBy('priority')" class="group inline-flex items-center gap-1">
                        Priority
                        @if ($sortField === 'priority')
                            <svg class="size-3.5 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 17a.75.75 0 0 1-.75-.75V5.612L5.29 9.77a.75.75 0 0 1-1.08-1.04l5.25-5.5a.75.75 0 0 1 1.08 0l5.25 5.5a.75.75 0 1 1-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0 1 10 17Z" clip-rule="evenodd"/></svg>
                        @else
                            <svg class="size-3.5 text-slate-300 dark:text-slate-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .55.24l3.25 3.5a.75.75 0 1 1-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 0 1-1.1-1.02l3.25-3.5A.75.75 0 0 1 10 3Zm-3.76 9.2a.75.75 0 0 1 1.06.04l2.7 2.908 2.7-2.908a.75.75 0 1 1 1.1 1.02l-3.25 3.5a.75.75 0 0 1-1.1 0l-3.25-3.5a.75.75 0 0 1 .04-1.06Z" clip-rule="evenodd"/></svg>
                        @endif
                    </button>
                </th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Actions</th>
            </x-slot:header>

            <x-slot:body>
                @foreach ($bonusTypes as $bonusType)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50">
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-slate-900 dark:text-white">{{ $bonusType->name }}</div>
                            @if ($bonusType->description)
                                <div class="mt-0.5 max-w-xs truncate text-xs text-slate-500 dark:text-slate-400">{{ $bonusType->description }}</div>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            @php
                                $typeBadgeColor = match($bonusType->type) {
                                    \App\Enums\BonusTypeEnum::Matching        => 'primary',
                                    \App\Enums\BonusTypeEnum::FastStart       => 'info',
                                    \App\Enums\BonusTypeEnum::RankAdvancement => 'warning',
                                    \App\Enums\BonusTypeEnum::PoolSharing     => 'success',
                                    \App\Enums\BonusTypeEnum::Leadership      => 'gray',
                                };
                            @endphp
                            <x-admin.badge :color="$typeBadgeColor">{{ $bonusType->type->label() }}</x-admin.badge>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            @if ($bonusType->is_active)
                                <x-admin.badge color="success">Active</x-admin.badge>
                            @else
                                <x-admin.badge color="gray">Inactive</x-admin.badge>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                            {{ $bonusType->priority }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                            <div class="flex items-center justify-end gap-1">
                                {{-- Toggle active --}}
                                <form method="POST" action="{{ route('admin.bonus-types.toggle', [$company, $plan, $bonusType]) }}">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="inline-flex size-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-amber-50 hover:text-amber-600 dark:hover:bg-amber-900/20 dark:hover:text-amber-400"
                                        title="{{ $bonusType->is_active ? 'Deactivate' : 'Activate' }}"
                                    >
                                        @if ($bonusType->is_active)
                                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                        @else
                                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                        @endif
                                    </button>
                                </form>

                                {{-- Edit --}}
                                <a href="{{ route('admin.companies.plans.bonus-types.edit', [$company, $plan, $bonusType]) }}" class="inline-flex size-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-indigo-50 hover:text-indigo-600 dark:hover:bg-indigo-900/20 dark:hover:text-indigo-400" title="Edit">
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                                </a>

                                {{-- Delete --}}
                                <form method="POST" action="{{ route('admin.companies.plans.bonus-types.destroy', [$company, $plan, $bonusType]) }}" onsubmit="return confirm('Delete this bonus type? This cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex size-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-900/20 dark:hover:text-rose-400" title="Delete">
                                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-slot:body>

            <x-slot:pagination>
                {{ $bonusTypes->links() }}
            </x-slot:pagination>
        </x-admin.data-table>
    @endif
</div>
