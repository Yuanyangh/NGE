<div
    x-data="{
        nodes: @js($this->treeData),
        expanded: {},
        search: '',
        toggle(id) {
            this.expanded[id] = !this.expanded[id];
        },
        isExpanded(id) {
            return this.expanded[id] === true;
        },
        rootNodes() {
            return this.filteredNodes().filter(n => n.sponsor_id === null);
        },
        childrenOf(parentId) {
            return this.filteredNodes().filter(n => n.sponsor_id === parentId);
        },
        hasChildren(id) {
            return this.nodes.some(n => n.sponsor_id === id);
        },
        filteredNodes() {
            if (!this.search.trim()) return this.nodes;
            const q = this.search.toLowerCase();
            return this.nodes.filter(n => n.name.toLowerCase().includes(q) || n.email.toLowerCase().includes(q));
        },
        riskDot(level) {
            return {
                'inactive_warning': 'bg-rose-600',
                'at_risk': 'bg-orange-500',
                'declining': 'bg-amber-400',
                'stagnant_leader': 'bg-blue-400',
                'healthy': 'bg-slate-300 dark:bg-slate-600',
            }[level] || 'bg-slate-300 dark:bg-slate-600';
        },
        riskLabel(level) {
            return level.replace(/_/g, ' ');
        },
        statusDot(status) {
            return status === 'active' ? 'bg-emerald-400' : 'bg-slate-300 dark:bg-slate-600';
        }
    }"
    class="flex flex-col gap-6 lg:flex-row lg:items-start"
>
    {{-- LEFT: Tree panel --}}
    <div class="min-w-0 flex-1">

        {{-- Stats bar --}}
        @php $stats = $this->stats; @endphp
        <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                <p class="text-2xl font-bold text-slate-900 tabular-nums dark:text-white">{{ number_format($stats['total']) }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Total Network</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                <p class="text-2xl font-bold text-emerald-600 tabular-nums dark:text-emerald-400">{{ number_format($stats['affiliates']) }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Affiliates</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                <p class="text-2xl font-bold text-amber-600 tabular-nums dark:text-amber-400">{{ number_format($stats['at_risk']) }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">At Risk</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                <p class="text-2xl font-bold text-slate-600 tabular-nums dark:text-slate-400">{{ number_format($stats['healthy']) }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Healthy</p>
            </div>
        </div>

        {{-- Search --}}
        <div class="mb-4">
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-3 flex items-center">
                    <svg class="size-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                </div>
                <input
                    type="text"
                    x-model.debounce.300ms="search"
                    placeholder="Search by name or email..."
                    class="block w-full rounded-lg border border-slate-300 bg-white py-2 pl-10 pr-4 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:placeholder-slate-400"
                />
            </div>
        </div>

        {{-- Risk legend --}}
        <div class="mb-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-slate-500 dark:text-slate-400">
            <span class="font-medium uppercase tracking-wider">Risk:</span>
            <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-slate-300 dark:bg-slate-600"></span>Healthy</span>
            <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-blue-400"></span>Stagnant</span>
            <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-amber-400"></span>Declining</span>
            <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-orange-500"></span>At risk</span>
            <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-rose-600"></span>Inactive</span>
        </div>

        {{-- Tree --}}
        <div class="space-y-1">
            <template x-for="n0 in rootNodes()" :key="n0.id">
                @include('livewire.admin.genealogy.partials.tree-node', ['depth' => 0])
            </template>

            <template x-if="rootNodes().length === 0">
                <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                    No nodes found.
                </div>
            </template>
        </div>
    </div>

    {{-- RIGHT: Side panel --}}
    @if ($selectedNodeId !== null && !empty($this->selectedNodeStats))
        @php
            $sel = $this->selectedNodeStats;
            $user = $sel['user'];
            $node = $sel['node'];
            $churnData = collect($this->treeData)->firstWhere('user_id', $user?->id);
        @endphp
        <aside class="w-full shrink-0 lg:w-72 xl:w-80">
            <div class="sticky top-24 rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start justify-between p-5 pb-4">
                    <div class="min-w-0">
                        <h3 class="truncate text-base font-semibold text-slate-900 dark:text-white">{{ $user?->name ?? 'Unknown' }}</h3>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $user?->email }}</p>
                    </div>
                    <button wire:click="clearSelection" class="ml-3 rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800">
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="space-y-4 p-5 pt-0">
                    <div class="flex items-center gap-2">
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $user?->status === 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }}">
                            {{ ucfirst($user?->status ?? 'unknown') }}
                        </span>
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-medium bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400">
                            {{ ucfirst($user?->role ?? 'customer') }}
                        </span>
                    </div>

                    @if ($churnData && $churnData['risk_level'] !== 'healthy')
                        @php
                            $riskColors = [
                                'inactive_warning' => 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-800/40 dark:bg-rose-900/20 dark:text-rose-400',
                                'at_risk' => 'border-orange-200 bg-orange-50 text-orange-800 dark:border-orange-800/40 dark:bg-orange-900/20 dark:text-orange-400',
                                'declining' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-800/40 dark:bg-amber-900/20 dark:text-amber-400',
                                'stagnant_leader' => 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-800/40 dark:bg-blue-900/20 dark:text-blue-400',
                            ];
                        @endphp
                        <div class="rounded-lg border p-3 {{ $riskColors[$churnData['risk_level']] ?? '' }}">
                            <p class="text-xs font-semibold uppercase tracking-wider">{{ str_replace('_', ' ', $churnData['risk_level']) }}</p>
                            <p class="mt-1 text-xs leading-relaxed">{{ $churnData['risk_reason'] }}</p>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800/50">
                            <p class="text-lg font-bold text-slate-900 tabular-nums dark:text-white">{{ number_format($sel['direct_downline']) }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Direct recruits</p>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800/50">
                            <p class="text-lg font-bold text-slate-900 tabular-nums dark:text-white">{{ number_format($sel['total_downline']) }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Total downline</p>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800/50">
                            <p class="text-lg font-bold text-slate-900 tabular-nums dark:text-white">{{ number_format((float)$sel['volume_30d'], 0) }} XP</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Volume (30d)</p>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800/50">
                            <p class="text-lg font-bold text-slate-900 tabular-nums dark:text-white">${{ number_format((float)$sel['earnings_30d'], 2) }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Earnings (30d)</p>
                        </div>
                    </div>

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
