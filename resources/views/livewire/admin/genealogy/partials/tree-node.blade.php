@php
    $userId    = $node->user_id;
    $user      = $node->user;
    $isExpanded= in_array($node->id, $expandedNodeIds, true);
    $children  = $childrenOf[$node->id] ?? [];
    $hasKnownChildren = !empty($children);

    // Determine risk level
    $churn     = $churnByUserId[$userId] ?? null;
    $riskLevel = $churn ? $churn->risk_level : null;
    $riskDot   = match($riskLevel) {
        'inactive_warning' => 'bg-rose-600',
        'at_risk'          => 'bg-orange-500',
        'declining'        => 'bg-amber-400',
        'stagnant_leader'  => 'bg-blue-400',
        default            => 'bg-slate-300 dark:bg-slate-600',
    };

    $isSelected = $selectedNodeId === $node->id;
@endphp

<div class="{{ $depth > 0 ? 'ml-6 mt-2 border-l-2 border-slate-200 pl-4 dark:border-slate-700' : 'mt-2' }}">
    {{-- Node card --}}
    <div
        wire:click="selectNode({{ $node->id }})"
        class="group flex cursor-pointer items-center gap-3 rounded-lg border p-3 transition-all duration-150 hover:shadow-sm
            {{ $isSelected
                ? 'border-indigo-300 bg-indigo-50 dark:border-indigo-700 dark:bg-indigo-900/20'
                : 'border-slate-200 bg-white hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700'
            }}"
    >
        {{-- Risk dot --}}
        <span class="size-2.5 shrink-0 rounded-full {{ $riskDot }}" title="{{ $riskLevel ? str_replace('_', ' ', $riskLevel) : 'healthy' }}"></span>

        {{-- Name + depth --}}
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium text-slate-900 dark:text-white">
                {{ $user?->name ?? 'Unknown' }}
            </p>
            @if ($depth === 0)
                <p class="text-xs text-slate-500 dark:text-slate-400">Root</p>
            @endif
        </div>

        {{-- Status dot --}}
        <span class="size-2 shrink-0 rounded-full {{ ($user?->status === 'active') ? 'bg-emerald-400' : 'bg-slate-300 dark:bg-slate-600' }}" title="{{ $user?->status ?? 'unknown' }}"></span>

        {{-- Expand/collapse button --}}
        @if ($depth < 5)
            @if ($isExpanded && $hasKnownChildren)
                <button
                    wire:click.stop="collapseNode({{ $node->id }})"
                    class="ml-1 shrink-0 rounded p-0.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
                    title="Collapse"
                    aria-label="Collapse children of {{ $user?->name }}"
                >
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5"/>
                    </svg>
                </button>
            @elseif (!$isExpanded)
                <button
                    wire:click.stop="expandNode({{ $node->id }})"
                    class="ml-1 shrink-0 rounded p-0.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
                    title="Expand"
                    aria-label="Expand children of {{ $user?->name }}"
                >
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                    </svg>
                </button>
            @endif
        @endif
    </div>

    {{-- Children (lazy loaded) --}}
    @if ($isExpanded && $hasKnownChildren)
        @foreach ($children as $child)
            @include('livewire.admin.genealogy.partials.tree-node', [
                'node'            => $child,
                'depth'           => $depth + 1,
                'churnByUserId'   => $churnByUserId,
                'expandedNodeIds' => $expandedNodeIds,
                'childrenOf'      => $childrenOf,
            ])
        @endforeach
    @endif
</div>
