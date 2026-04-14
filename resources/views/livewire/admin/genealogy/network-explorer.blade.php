{{-- ============================================================
     Network Explorer
     Calls: ChurnDetector, GenealogyNode tree with lazy expansion
     ============================================================ --}}

<style>
    @media print {
        aside, header, .no-print { display: none !important; }
        main { padding: 0 !important; }
        .lg\:pl-64 { padding-left: 0 !important; }
        body { background: #fff !important; }
    }
</style>

<div class="flex flex-col gap-6 lg:flex-row lg:items-start">

    {{-- ── LEFT: Tree panel ──────────────────────────────────────────── --}}
    <div class="min-w-0 flex-1">

        {{-- Search bar --}}
        <div class="no-print mb-4">
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-3 flex items-center">
                    <svg class="size-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                </div>
                <input
                    type="text"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Search affiliates by name..."
                    class="block w-full rounded-lg border border-slate-300 bg-white py-2 pl-10 pr-4 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:placeholder-slate-400"
                />
            </div>
        </div>

        {{-- Risk legend --}}
        <div class="no-print mb-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-slate-500 dark:text-slate-400">
            <span class="font-medium uppercase tracking-wider">Risk indicator:</span>
            <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-slate-300 dark:bg-slate-600"></span>Healthy</span>
            <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-blue-400"></span>Stagnant leader</span>
            <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-amber-400"></span>Declining</span>
            <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-orange-500"></span>At risk</span>
            <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-rose-600"></span>Inactive warning</span>
        </div>

        {{-- Loading indicator --}}
        <div wire:loading class="mb-4 flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
            <svg class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            Loading...
        </div>

        {{-- Tree nodes --}}
        <div wire:loading.class="opacity-50">
            @forelse ($this->rootNodes as $node)
                @include('livewire.admin.genealogy.partials.tree-node', [
                    'node'           => $node,
                    'depth'          => 0,
                    'churnByUserId'  => $this->churnByUserId,
                    'expandedNodeIds'=> $expandedNodeIds,
                    'childrenOf'     => $this->childrenOf,
                ])
            @empty
                <x-admin.empty-state message="No affiliates found{{ $search ? ' matching \"' . $search . '\"' : '' }}." />
            @endforelse
        </div>
    </div>

    {{-- ── RIGHT: Side panel ────────────────────────────────────────── --}}
    @if ($selectedNodeId !== null && $this->selectedNode !== null)
        @php
            $node    = $this->selectedNode;
            $user    = $node->user;
            $stats   = $this->selectedNodeStats;
            $churn   = $this->churnByUserId[$user?->id] ?? null;
        @endphp
        <aside class="w-full shrink-0 lg:w-72 xl:w-80">
            <div class="sticky top-24 rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                {{-- Header --}}
                <div class="flex items-start justify-between p-5 pb-4">
                    <div class="min-w-0">
                        <h3 class="truncate text-base font-semibold text-slate-900 dark:text-white">
                            {{ $user?->name ?? 'Unknown' }}
                        </h3>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">ID #{{ $user?->id }}</p>
                    </div>
                    <button
                        wire:click="selectNode({{ $node->id }})"
                        class="ml-3 rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-300"
                        aria-label="Close side panel"
                    >
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-4 p-5 pt-0">
                    {{-- Status --}}
                    <div class="flex items-center gap-2">
                        @php
                            $statusColor = match($user?->status) {
                                'active'   => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                                'inactive' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
                                default    => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
                            };
                        @endphp
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColor }}">
                            {{ ucfirst($user?->status ?? 'unknown') }}
                        </span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">
                            Depth {{ $node->tree_depth ?? 0 }}
                        </span>
                    </div>

                    {{-- Churn risk badge --}}
                    @if ($churn)
                        @php
                            $churnColors = [
                                'inactive_warning' => 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-800/40 dark:bg-rose-900/20 dark:text-rose-400',
                                'at_risk'          => 'border-orange-200 bg-orange-50 text-orange-800 dark:border-orange-800/40 dark:bg-orange-900/20 dark:text-orange-400',
                                'declining'        => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-800/40 dark:bg-amber-900/20 dark:text-amber-400',
                                'stagnant_leader'  => 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-800/40 dark:bg-blue-900/20 dark:text-blue-400',
                            ];
                            $cc = $churnColors[$churn['risk_level']] ?? '';
                        @endphp
                        <div class="rounded-lg border p-3 {{ $cc }}">
                            <p class="text-xs font-semibold uppercase tracking-wider">
                                {{ str_replace('_', ' ', $churn['risk_level']) }}
                            </p>
                            <p class="mt-1 text-xs leading-relaxed">{{ $churn['reason'] }}</p>
                        </div>
                    @endif

                    {{-- Stats grid --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800/50">
                            <p class="text-lg font-bold text-slate-900 tabular-nums dark:text-white">{{ number_format($stats['direct_downline'] ?? 0) }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Direct recruits</p>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800/50">
                            <p class="text-lg font-bold text-slate-900 tabular-nums dark:text-white">{{ number_format($stats['total_downline'] ?? 0) }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Total downline</p>
                        </div>
                        <div class="col-span-2 rounded-lg bg-slate-50 p-3 dark:bg-slate-800/50">
                            <p class="text-lg font-bold text-slate-900 tabular-nums dark:text-white">{{ number_format((float)($stats['volume_30d'] ?? 0), 0) }} XP</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Volume — last 30 days</p>
                        </div>
                    </div>

                    {{-- Enrolled at --}}
                    @if ($user?->enrolled_at)
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Enrolled {{ \Carbon\Carbon::parse($user->enrolled_at)->format('M j, Y') }}
                        </p>
                    @endif
                </div>
            </div>
        </aside>
    @endif
</div>
