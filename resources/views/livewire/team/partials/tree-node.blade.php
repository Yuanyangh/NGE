@php
    $hasChildren = !empty($node['children']);
    $defaultOpen  = $node['depth'] < 1;  // depth 0 children (depth-1 nodes) open by default; depth 2+ collapsed
@endphp

<li x-data="{ open: {{ $defaultOpen ? 'true' : 'false' }} }">
    {{-- Node card --}}
    <div class="inline-block px-3 py-2 bg-white rounded-lg shadow-sm border text-center min-w-[120px] max-w-[160px]
        {{ $hasChildren ? 'cursor-pointer' : '' }}
        {{ $node['is_root'] ? 'border-indigo-400 ring-1 ring-indigo-100' : 'border-gray-200' }}"
        @if($hasChildren) @click="open = !open" @endif>

        {{-- Name --}}
        <div class="font-semibold text-sm text-gray-900 truncate" title="{{ $node['name'] }}">
            {{ $node['name'] }}
        </div>

        {{-- Role badge + status dot --}}
        <div class="mt-1 flex items-center justify-center gap-1 flex-wrap">
            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                {{ $node['role'] === 'affiliate' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600' }}">
                {{ ucfirst($node['role']) }}
            </span>
            <span
                class="inline-block w-2 h-2 rounded-full flex-shrink-0
                    {{ $node['status'] === 'active' ? 'bg-green-500' : 'bg-gray-300' }}"
                title="{{ ucfirst($node['status']) }}"
                aria-label="{{ $node['status'] === 'active' ? 'Active' : 'Inactive' }}"
            ></span>
        </div>

        {{-- Personal Volume --}}
        <div class="mt-2">
            <div class="text-xs text-gray-500 leading-none">Volume</div>
            <div class="text-sm font-semibold text-gray-900 mt-0.5">
                {{ number_format((float) $node['personal_volume'], 0) }} XP
            </div>
        </div>

        {{-- Direct recruits --}}
        <div class="mt-1">
            <div class="text-xs text-gray-400">
                {{ $node['direct_recruits'] }}
                {{ $node['direct_recruits'] === 1 ? 'recruit' : 'recruits' }}
            </div>
        </div>

        {{-- Expand / collapse indicator --}}
        @if($hasChildren)
            <div class="mt-2 pt-1.5 border-t border-gray-100 flex items-center justify-center gap-1 text-xs text-gray-400 select-none">
                <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
                <svg x-show="open" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                </svg>
                <span x-text="open ? 'Collapse' : '{{ count($node['children']) }} more'"></span>
            </div>
        @endif
    </div>

    {{-- Children --}}
    @if($hasChildren)
        <ul x-show="open" x-collapse>
            @foreach ($node['children'] as $child)
                @include('livewire.team.partials.tree-node', ['node' => $child])
            @endforeach
        </ul>
    @endif
</li>
