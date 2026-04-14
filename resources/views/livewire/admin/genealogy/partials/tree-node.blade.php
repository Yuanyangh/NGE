{{-- Alpine.js recursive tree node - depth-indexed variable names to avoid shadowing --}}
@php $d = $depth ?? 0; $v = 'n' . $d; @endphp

<div class="{{ $d > 0 ? 'ml-5 border-l-2 border-slate-200 pl-3 dark:border-slate-700' : '' }}">
    <div
        @click="$wire.selectNode({{ $v }}.id)"
        class="group mt-1.5 flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 bg-white p-3 transition-all hover:border-slate-300 hover:shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700"
        :class="$wire.selectedNodeId === {{ $v }}.id && 'ring-2 ring-indigo-400 dark:ring-indigo-500'"
    >
        <span class="size-2.5 shrink-0 rounded-full" :class="riskDot({{ $v }}.risk_level)"></span>
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium text-slate-900 dark:text-white" x-text="{{ $v }}.name"></p>
            <p class="text-xs capitalize text-slate-500 dark:text-slate-400" x-text="{{ $v }}.role"></p>
        </div>
        <span class="size-2 shrink-0 rounded-full" :class="statusDot({{ $v }}.status)"></span>
        <template x-if="hasChildren({{ $v }}.id)">
            <button @click.stop="toggle({{ $v }}.id)" type="button"
                class="ml-1 shrink-0 rounded p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-300">
                <svg class="size-4 transition-transform duration-200" :class="isExpanded({{ $v }}.id) && 'rotate-180'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                </svg>
            </button>
        </template>
    </div>

    @if ($d < 5)
        @php $nextVar = 'n' . ($d + 1); @endphp
        <template x-if="isExpanded({{ $v }}.id)">
            <div>
                <template x-for="{{ $nextVar }} in childrenOf({{ $v }}.id)" :key="{{ $nextVar }}.id">
                    @include('livewire.admin.genealogy.partials.tree-node', ['depth' => $d + 1])
                </template>
            </div>
        </template>
    @endif
</div>
