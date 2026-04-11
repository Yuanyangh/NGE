<div>
    {{-- ================================================================
         SECTION 1: Basic Information
    ================================================================ --}}
    <x-admin.form-section title="Basic Information" description="Name this bonus and choose what type of bonus it is. The type determines which configuration options appear below.">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    Bonus Name <span class="text-rose-500">*</span>
                </label>
                <input
                    type="text"
                    name="name"
                    id="name"
                    wire:model="name"
                    value="{{ old('name', $this->name) }}"
                    placeholder="e.g. Generation Matching Bonus"
                    required
                    class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:placeholder:text-slate-500 dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                >
                @error('name')
                    <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Description</label>
                <textarea
                    name="description"
                    id="description"
                    wire:model="description"
                    rows="2"
                    placeholder="Optional: a short explanation of this bonus for your own reference."
                    class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:placeholder:text-slate-500 dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                >{{ old('description', $this->description) }}</textarea>
            </div>

            <div>
                <label for="type" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    Bonus Type <span class="text-rose-500">*</span>
                </label>
                <select
                    name="type"
                    id="type"
                    wire:model.live="type"
                    required
                    class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                >
                    <option value="">Select a bonus type...</option>
                    @foreach ($bonusTypeOptions as $value => $label)
                        <option value="{{ $value }}" @selected($this->type === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('type')
                    <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror

                {{-- Type descriptions --}}
                @if ($this->type === 'matching')
                    <p class="mt-2 rounded-lg bg-indigo-50 px-3 py-2 text-xs text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-300">
                        <strong>Matching Bonus:</strong> Affiliates earn a percentage of the commissions earned by their downline, generation by generation. Gen 1 = direct recruits, Gen 2 = their recruits, and so on.
                    </p>
                @elseif ($this->type === 'fast_start')
                    <p class="mt-2 rounded-lg bg-sky-50 px-3 py-2 text-xs text-sky-700 dark:bg-sky-900/20 dark:text-sky-300">
                        <strong>Fast Start Bonus:</strong> New affiliates earn an enhanced commission rate during their first N days. Rewards fast action and early momentum.
                    </p>
                @elseif ($this->type === 'rank_advancement')
                    <p class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                        <strong>Rank Advancement Bonus:</strong> A one-time cash bonus paid when an affiliate achieves a new rank. Each rank tier has its own qualifier and bonus amount.
                    </p>
                @elseif ($this->type === 'pool_sharing')
                    <p class="mt-2 rounded-lg bg-emerald-50 px-3 py-2 text-xs text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                        <strong>Pool Sharing Bonus:</strong> A percentage of total company revenue is pooled and distributed equally (or by volume) among qualifying top-rank affiliates.
                    </p>
                @elseif ($this->type === 'leadership')
                    <p class="mt-2 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                        <strong>Leadership Bonus:</strong> A recurring monthly cash bonus paid to affiliates who maintain a qualifying rank. Higher ranks earn larger monthly amounts.
                    </p>
                @endif
            </div>

            <div>
                <label for="priority" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Priority</label>
                <input
                    type="number"
                    name="priority"
                    id="priority"
                    wire:model="priority"
                    value="{{ old('priority', $this->priority) }}"
                    min="0"
                    placeholder="0"
                    class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:placeholder:text-slate-500 dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                >
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Lower number = processed first. Use 0 for default ordering.</p>
            </div>

            <div class="sm:col-span-2">
                <x-admin.toggle name="is_active" label="Active — this bonus type will run during commission calculations" :checked="old('is_active', $this->isActive)" />
            </div>
        </div>
    </x-admin.form-section>

    {{-- ================================================================
         TYPE-SPECIFIC CONFIGURATION
         Only renders when a type is selected
    ================================================================ --}}

    {{-- MATCHING: Generation tiers --}}
    @if ($this->type === 'matching')
        <div class="mt-6">
            <x-admin.form-section title="Generation Tiers" description="Set a commission percentage for each generation (level) of your downline. Gen 1 = your direct recruits. Gen 2 = their recruits. Add as many generations as your plan supports.">
                <div x-data="{ tiers: @entangle('matchingTiers') }">
                    <div class="space-y-3">
                        <template x-for="(tier, index) in tiers" :key="index">
                            <div class="flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/50">
                                {{-- Level badge --}}
                                <div class="flex size-7 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 mt-1" x-text="'G' + (index + 1)"></div>

                                <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-start">
                                    <div class="flex-1">
                                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">Label <span class="font-normal text-slate-400">(optional)</span></label>
                                        <input
                                            type="text"
                                            :name="'tiers[' + index + '][label]'"
                                            x-model="tier.label"
                                            placeholder="e.g. First Generation"
                                            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:placeholder:text-slate-500"
                                        >
                                    </div>
                                    <div class="w-full sm:w-36">
                                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">Rate % <span class="text-rose-500">*</span></label>
                                        <div class="relative mt-1">
                                            <input
                                                type="number"
                                                :name="'tiers[' + index + '][rate]'"
                                                x-model="tier.rate"
                                                placeholder="e.g. 5"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                class="block w-full rounded-lg border border-slate-300 bg-white py-2 pl-3 pr-8 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:placeholder:text-slate-500"
                                            >
                                            <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">%</span>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    @click="tiers.length > 1 && tiers.splice(index, 1)"
                                    :disabled="tiers.length <= 1"
                                    class="mt-1 inline-flex size-7 shrink-0 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-500 disabled:cursor-not-allowed disabled:opacity-30 dark:hover:bg-rose-900/20 dark:hover:text-rose-400"
                                    title="Remove this generation"
                                >
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </template>
                    </div>

                    <button
                        type="button"
                        @click="tiers.push({ label: '', rate: '' })"
                        class="mt-3 inline-flex items-center gap-2 rounded-lg border border-dashed border-slate-300 px-4 py-2 text-sm text-slate-500 transition-colors hover:border-indigo-400 hover:text-indigo-600 dark:border-slate-600 dark:text-slate-400 dark:hover:border-indigo-500 dark:hover:text-indigo-400"
                    >
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Add Generation
                    </button>
                </div>
            </x-admin.form-section>
        </div>
    @endif

    {{-- FAST START: Duration and rate --}}
    @if ($this->type === 'fast_start')
        <div class="mt-6">
            <x-admin.form-section title="Fast Start Settings" description="Configure how long the fast start period lasts and how much the commission rate is enhanced during that window.">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <div>
                        <label for="duration_days" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Duration (Days) <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="number"
                            name="duration_days"
                            id="duration_days"
                            wire:model="durationDays"
                            value="{{ old('duration_days', $this->durationDays) }}"
                            min="1"
                            placeholder="30"
                            required
                            class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:placeholder:text-slate-500"
                        >
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">How many days after joining the enhanced rate applies.</p>
                    </div>

                    <div>
                        <label for="multiplier_rate" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Enhanced Rate Multiplier <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="number"
                            name="multiplier_rate"
                            id="multiplier_rate"
                            wire:model="multiplierRate"
                            value="{{ old('multiplier_rate', $this->multiplierRate) }}"
                            min="1"
                            step="0.1"
                            placeholder="2.0"
                            required
                            class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:placeholder:text-slate-500"
                        >
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">2.0 = double the normal rate. 1.5 = 50% more than normal.</p>
                    </div>

                    <div>
                        <label for="applies_to" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Applies To <span class="text-rose-500">*</span>
                        </label>
                        <select
                            name="applies_to"
                            id="applies_to"
                            wire:model="appliesTo"
                            required
                            class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                        >
                            <option value="affiliate" @selected(old('applies_to', $this->appliesTo) === 'affiliate')>Affiliate Commission only</option>
                            <option value="viral" @selected(old('applies_to', $this->appliesTo) === 'viral')>Viral Commission only</option>
                            <option value="both" @selected(old('applies_to', $this->appliesTo) === 'both')>Both commission types</option>
                        </select>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Which commission streams benefit from the enhanced rate.</p>
                    </div>
                </div>
            </x-admin.form-section>
        </div>
    @endif

    {{-- RANK ADVANCEMENT: Rank tiers --}}
    @if ($this->type === 'rank_advancement')
        <div class="mt-6">
            <x-admin.form-section title="Rank Tiers" description="Define each rank and the one-time cash bonus awarded when an affiliate reaches it. Add a row for every rank in your plan.">
                <div x-data="{ tiers: @entangle('rankTiers') }">
                    <div class="space-y-3">
                        <template x-for="(tier, index) in tiers" :key="index">
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/50">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400" x-text="'Rank ' + (index + 1)"></span>
                                    <button
                                        type="button"
                                        @click="tiers.length > 1 && tiers.splice(index, 1)"
                                        :disabled="tiers.length <= 1"
                                        class="inline-flex size-6 items-center justify-center rounded text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-500 disabled:cursor-not-allowed disabled:opacity-30 dark:hover:bg-rose-900/20 dark:hover:text-rose-400"
                                        title="Remove rank"
                                    >
                                        <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">Rank Name <span class="font-normal text-slate-400">(optional)</span></label>
                                        <input type="text" :name="'tiers[' + index + '][label]'" x-model="tier.label" placeholder="e.g. Silver" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">Qualifier Type <span class="text-rose-500">*</span></label>
                                        <select :name="'tiers[' + index + '][qualifier_type]'" x-model="tier.qualifier_type" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                                            <option value="min_customers">Min Active Customers</option>
                                            <option value="min_volume">Min Sales Volume</option>
                                            <option value="min_downline">Min Downline Size</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">Qualifier Value <span class="text-rose-500">*</span></label>
                                        <input type="number" :name="'tiers[' + index + '][qualifier_value]'" x-model="tier.qualifier_value" min="0" step="0.01" placeholder="0" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">One-Time Bonus ($) <span class="text-rose-500">*</span></label>
                                        <div class="relative mt-1">
                                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">$</span>
                                            <input type="number" :name="'tiers[' + index + '][bonus_amount]'" x-model="tier.bonus_amount" min="0" step="0.01" placeholder="0.00" class="block w-full rounded-lg border border-slate-300 bg-white py-2 pl-6 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <button
                        type="button"
                        @click="tiers.push({ label: '', qualifier_value: '', qualifier_type: 'min_customers', bonus_amount: '' })"
                        class="mt-3 inline-flex items-center gap-2 rounded-lg border border-dashed border-slate-300 px-4 py-2 text-sm text-slate-500 transition-colors hover:border-indigo-400 hover:text-indigo-600 dark:border-slate-600 dark:text-slate-400 dark:hover:border-indigo-500 dark:hover:text-indigo-400"
                    >
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Add Rank
                    </button>
                </div>
            </x-admin.form-section>
        </div>
    @endif

    {{-- POOL SHARING --}}
    @if ($this->type === 'pool_sharing')
        <div class="mt-6">
            <x-admin.form-section title="Pool Sharing Settings" description="Define the size of the bonus pool and how it is distributed among qualifying affiliates.">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <div>
                        <label for="pool_percent" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Pool Size (% of company revenue) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative mt-1.5">
                            <input
                                type="number"
                                name="pool_percent"
                                id="pool_percent"
                                wire:model="poolPercent"
                                value="{{ old('pool_percent', $this->poolPercent) }}"
                                min="0"
                                max="100"
                                step="0.01"
                                placeholder="5"
                                required
                                class="block w-full rounded-lg border border-slate-300 bg-white py-2 pl-3 pr-8 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:placeholder:text-slate-500"
                            >
                            <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">%</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">e.g. 5 = 5% of total company sales goes into this pool.</p>
                    </div>

                    <div>
                        <label for="distribution_method" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Distribution Method <span class="text-rose-500">*</span>
                        </label>
                        <select
                            name="distribution_method"
                            id="distribution_method"
                            wire:model="distributionMethod"
                            required
                            class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                        >
                            <option value="equal" @selected(old('distribution_method', $this->distributionMethod) === 'equal')>Equal share — everyone gets the same amount</option>
                            <option value="volume_weighted" @selected(old('distribution_method', $this->distributionMethod) === 'volume_weighted')>Volume weighted — higher volume earns more</option>
                        </select>
                    </div>

                    <div>
                        <label for="qualifying_min_rank" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Minimum Qualifying Rank <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="number"
                            name="qualifying_min_rank"
                            id="qualifying_min_rank"
                            wire:model="qualifyingMinRank"
                            value="{{ old('qualifying_min_rank', $this->qualifyingMinRank) }}"
                            min="1"
                            placeholder="1"
                            required
                            class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:placeholder:text-slate-500"
                        >
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Affiliates must be at or above this rank level to qualify.</p>
                    </div>
                </div>
            </x-admin.form-section>
        </div>
    @endif

    {{-- LEADERSHIP: Monthly tier bonuses --}}
    @if ($this->type === 'leadership')
        <div class="mt-6">
            <x-admin.form-section title="Leadership Rank Tiers" description="Set a recurring monthly bonus for each qualifying rank. Affiliates who maintain the rank receive the amount automatically each month.">
                <div x-data="{ tiers: @entangle('leadershipTiers') }">
                    <div class="space-y-3">
                        <template x-for="(tier, index) in tiers" :key="index">
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/50">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400" x-text="'Tier ' + (index + 1)"></span>
                                    <button
                                        type="button"
                                        @click="tiers.length > 1 && tiers.splice(index, 1)"
                                        :disabled="tiers.length <= 1"
                                        class="inline-flex size-6 items-center justify-center rounded text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-500 disabled:cursor-not-allowed disabled:opacity-30 dark:hover:bg-rose-900/20 dark:hover:text-rose-400"
                                        title="Remove tier"
                                    >
                                        <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">Rank Name <span class="font-normal text-slate-400">(optional)</span></label>
                                        <input type="text" :name="'tiers[' + index + '][label]'" x-model="tier.label" placeholder="e.g. Gold Leader" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">Qualifier Type <span class="text-rose-500">*</span></label>
                                        <select :name="'tiers[' + index + '][qualifier_type]'" x-model="tier.qualifier_type" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                                            <option value="min_customers">Min Active Customers</option>
                                            <option value="min_volume">Min Sales Volume</option>
                                            <option value="min_downline">Min Downline Size</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">Qualifier Value <span class="text-rose-500">*</span></label>
                                        <input type="number" :name="'tiers[' + index + '][qualifier_value]'" x-model="tier.qualifier_value" min="0" step="0.01" placeholder="0" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">Monthly Bonus ($) <span class="text-rose-500">*</span></label>
                                        <div class="relative mt-1">
                                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">$</span>
                                            <input type="number" :name="'tiers[' + index + '][monthly_amount]'" x-model="tier.monthly_amount" min="0" step="0.01" placeholder="0.00" class="block w-full rounded-lg border border-slate-300 bg-white py-2 pl-6 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <button
                        type="button"
                        @click="tiers.push({ label: '', qualifier_value: '', qualifier_type: 'min_customers', monthly_amount: '' })"
                        class="mt-3 inline-flex items-center gap-2 rounded-lg border border-dashed border-slate-300 px-4 py-2 text-sm text-slate-500 transition-colors hover:border-indigo-400 hover:text-indigo-600 dark:border-slate-600 dark:text-slate-400 dark:hover:border-indigo-500 dark:hover:text-indigo-400"
                    >
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Add Tier
                    </button>
                </div>
            </x-admin.form-section>
        </div>
    @endif

    {{-- Placeholder when no type is selected --}}
    @if ($this->type === '')
        <div class="mt-6 rounded-xl border border-dashed border-slate-300 bg-white px-6 py-8 text-center dark:border-slate-700 dark:bg-slate-900">
            <svg class="mx-auto size-8 text-slate-300 dark:text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
            </svg>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Select a bonus type above to see its configuration options.</p>
        </div>
    @endif
</div>
