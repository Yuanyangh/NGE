<x-admin-layout title="Create Compensation Plan">
    <x-admin.page-header title="Create Compensation Plan" description="Configure a new compensation plan.">
        <x-slot:actions>
            <a href="{{ route('admin.compensation-plans.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
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
        <form method="POST" action="{{ route('admin.compensation-plans.store') }}">
            @csrf

            {{-- ================================================================
                 SECTION 1: Plan Details
            ================================================================ --}}
            <x-admin.form-section title="Plan Details" description="Basic plan information and company assignment.">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-admin.select
                        name="company_id"
                        label="Company"
                        :options="\App\Models\Company::orderBy('name')->pluck('name', 'id')"
                        :value="old('company_id')"
                        required
                        placeholder="Select a company"
                    />
                    <x-admin.input name="name" label="Plan Name" :value="old('name')" required placeholder="SoComm Plan v1" />
                    <x-admin.input name="version" label="Version" :value="old('version')" required placeholder="1.0" />
                    <div></div>
                    <x-admin.input name="effective_from" label="Effective From" type="date" :value="old('effective_from')" required />
                    <x-admin.input name="effective_until" label="Effective Until" type="date" :value="old('effective_until')" />
                </div>
                <div class="mt-5">
                    <x-admin.toggle name="is_active" label="Active" :checked="old('is_active', true)" />
                </div>
            </x-admin.form-section>

            {{-- ================================================================
                 SECTION 2: Plan Settings
            ================================================================ --}}
            <div class="mt-6">
                <x-admin.form-section title="Plan Settings" description="Core parameters that control how and when commissions are calculated and paid.">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                        <div>
                            <x-admin.select
                                name="config[plan][currency]"
                                label="Currency"
                                :value="old('config.plan.currency', $defaults['plan']['currency'])"
                                required
                                :options="['USD' => 'USD — US Dollar', 'EUR' => 'EUR — Euro', 'GBP' => 'GBP — British Pound', 'CAD' => 'CAD — Canadian Dollar', 'AUD' => 'AUD — Australian Dollar', 'MYR' => 'MYR — Malaysian Ringgit']"
                            />
                            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">The currency used for all commission calculations and payouts.</p>
                        </div>

                        <div>
                            <x-admin.select
                                name="config[plan][calculation_frequency]"
                                label="Calculation Frequency"
                                :value="old('config.plan.calculation_frequency', $defaults['plan']['calculation_frequency'])"
                                required
                                :options="['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly']"
                            />
                            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">How often commissions are calculated. "Daily" means the engine runs every day.</p>
                        </div>

                        <div>
                            <x-admin.select
                                name="config[plan][credit_frequency]"
                                label="Credit Frequency"
                                :value="old('config.plan.credit_frequency', $defaults['plan']['credit_frequency'])"
                                required
                                :options="['weekly' => 'Weekly', 'monthly' => 'Monthly']"
                            />
                            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">How often earned commissions are credited to affiliate wallets.</p>
                        </div>

                        <div>
                            <x-admin.select
                                name="config[plan][timezone]"
                                label="Timezone"
                                :value="old('config.plan.timezone', $defaults['plan']['timezone'])"
                                required
                                :options="['UTC' => 'UTC', 'America/New_York' => 'America/New_York (ET)', 'America/Chicago' => 'America/Chicago (CT)', 'America/Denver' => 'America/Denver (MT)', 'America/Los_Angeles' => 'America/Los_Angeles (PT)', 'Asia/Kuala_Lumpur' => 'Asia/Kuala_Lumpur (MYT)', 'Europe/London' => 'Europe/London (GMT/BST)']"
                            />
                            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">The timezone used to define when each "day" starts and ends for calculations.</p>
                        </div>

                    </div>
                </x-admin.form-section>
            </div>

            {{-- ================================================================
                 SECTION 3: Qualification Rules
            ================================================================ --}}
            <div class="mt-6">
                <x-admin.form-section title="Qualification Rules" description="Define what makes a customer 'active' and how qualification periods work.">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                        <div>
                            <x-admin.input
                                name="config[qualification][rolling_days]"
                                label="Rolling Period (Days)"
                                type="number"
                                :value="old('config.qualification.rolling_days', $defaults['qualification']['rolling_days'])"
                                required
                                placeholder="30"
                            />
                            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">The number of days to look back when calculating volumes and active customers. For example, 30 means "the last 30 days".</p>
                        </div>

                        <div>
                            <x-admin.input
                                name="config[qualification][active_customer_min_order_xp]"
                                label="Minimum Order Points for Active Customer"
                                type="number"
                                step="1"
                                :value="old('config.qualification.active_customer_min_order_xp', $defaults['qualification']['active_customer_min_order_xp'])"
                                required
                                placeholder="20"
                            />
                            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">A customer must spend at least this many points (XP) in a single order to count as "active".</p>
                        </div>

                        <div class="sm:col-span-2">
                            <x-admin.select
                                name="config[qualification][active_customer_threshold_type]"
                                label="Active Customer Threshold"
                                :value="old('config.qualification.active_customer_threshold_type', $defaults['qualification']['active_customer_threshold_type'])"
                                required
                                :options="['per_order' => 'Per Order — Each order must meet the minimum', 'cumulative' => 'Cumulative — Total orders in the period must meet the minimum']"
                            />
                            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">How to measure whether a customer meets the minimum order requirement.</p>
                        </div>

                    </div>
                </x-admin.form-section>
            </div>

            {{-- ================================================================
                 SECTION 4: Affiliate Commission
            ================================================================ --}}
            <div class="mt-6">
                <x-admin.form-section title="Affiliate Commission" description="Configure how affiliates earn commissions from their directly referred customers' purchases.">

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <x-admin.select
                                name="config[affiliate_commission][payout_method]"
                                label="Payout Method"
                                :value="old('config.affiliate_commission.payout_method', $defaults['affiliate_commission']['payout_method'])"
                                required
                                :options="['daily_new_volume' => 'Daily New Volume — Pay on each day\'s new qualifying purchases', 'cumulative' => 'Cumulative — Pay on total volume in the period']"
                            />
                            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Determines how commission is calculated each day.</p>
                        </div>
                        <div></div>
                    </div>

                    <div class="mt-5 space-y-4">
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-700 dark:bg-slate-800/50">
                            <x-admin.toggle
                                name="config[affiliate_commission][self_purchase_earns_commission]"
                                label="Self-Purchase Earns Commission"
                                :checked="old('config.affiliate_commission.self_purchase_earns_commission', $defaults['affiliate_commission']['self_purchase_earns_commission'])"
                            />
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">If enabled, affiliates earn commission on their own purchases.</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-700 dark:bg-slate-800/50">
                            <x-admin.toggle
                                name="config[affiliate_commission][includes_smartship]"
                                label="Include SmartShip Orders"
                                :checked="old('config.affiliate_commission.includes_smartship', $defaults['affiliate_commission']['includes_smartship'])"
                            />
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">If enabled, recurring subscription (SmartShip) orders count toward commission calculations.</p>
                        </div>
                    </div>

                    {{-- Affiliate Commission Tiers --}}
                    <div class="mt-6"
                        x-data="{
                            tiers: {{ Js::from(old('config.affiliate_commission.tiers', $defaults['affiliate_commission']['tiers'])) }},
                            addTier() {
                                this.tiers.push({ min_active_customers: 1, min_referred_volume: 0, rate: 10 });
                            },
                            removeTier(index) {
                                if (this.tiers.length > 1) this.tiers.splice(index, 1);
                            }
                        }"
                    >
                        <div class="mb-3 flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-200">Affiliate Commission Tiers</h4>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Define commission rates based on customer count and volume thresholds. Tiers are evaluated from top to bottom — the highest matching tier applies.</p>
                            </div>
                        </div>

                        {{-- Table header --}}
                        <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                <thead class="bg-slate-50 dark:bg-slate-800">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Tier</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                            Min Active Customers
                                            <span class="block font-normal normal-case text-slate-400 dark:text-slate-500">Minimum referred active customers to qualify</span>
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                            Min Referred Volume ($)
                                            <span class="block font-normal normal-case text-slate-400 dark:text-slate-500">Minimum volume from referred customers</span>
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                            Commission Rate (%)
                                            <span class="block font-normal normal-case text-slate-400 dark:text-slate-500">Percentage of qualifying volume paid</span>
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                            <span class="sr-only">Actions</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white dark:divide-slate-800 dark:bg-slate-900">
                                    <template x-for="(tier, index) in tiers" :key="index">
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400">
                                                <span x-text="index + 1" class="inline-flex size-7 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300"></span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <input
                                                    type="number"
                                                    :name="`config[affiliate_commission][tiers][${index}][min_active_customers]`"
                                                    x-model="tier.min_active_customers"
                                                    min="0"
                                                    step="1"
                                                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                                                    required
                                                >
                                            </td>
                                            <td class="px-4 py-3">
                                                <input
                                                    type="number"
                                                    :name="`config[affiliate_commission][tiers][${index}][min_referred_volume]`"
                                                    x-model="tier.min_referred_volume"
                                                    min="0"
                                                    step="0.01"
                                                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                                                    required
                                                >
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="relative">
                                                    <input
                                                        type="number"
                                                        :name="`config[affiliate_commission][tiers][${index}][rate]`"
                                                        x-model="tier.rate"
                                                        min="0"
                                                        max="100"
                                                        step="0.01"
                                                        class="block w-full rounded-lg border border-slate-300 bg-white py-2 pl-3 pr-8 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                                                        required
                                                    >
                                                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">%</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <button
                                                    type="button"
                                                    @click="removeTier(index)"
                                                    :disabled="tiers.length === 1"
                                                    :class="tiers.length === 1 ? 'cursor-not-allowed opacity-30' : 'hover:text-rose-600 dark:hover:text-rose-400'"
                                                    class="inline-flex size-8 cursor-pointer items-center justify-center rounded-lg text-slate-400 transition-colors focus:outline-none focus:ring-2 focus:ring-rose-500"
                                                    aria-label="Remove tier"
                                                >
                                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <button
                            type="button"
                            @click="addTier()"
                            class="mt-3 inline-flex cursor-pointer items-center gap-2 rounded-lg border border-dashed border-indigo-300 px-4 py-2 text-sm font-medium text-indigo-600 transition-colors hover:border-indigo-400 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-indigo-700 dark:text-indigo-400 dark:hover:border-indigo-600 dark:hover:bg-indigo-900/20"
                        >
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            Add Tier
                        </button>
                    </div>

                </x-admin.form-section>
            </div>

            {{-- ================================================================
                 SECTION 5: Viral (Network) Commission
            ================================================================ --}}
            <div class="mt-6">
                <x-admin.form-section title="Viral (Network) Commission" description="Configure how affiliates earn commissions from their extended network (downline) activity.">

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-admin.select
                                name="config[viral_commission][tree]"
                                label="Network Tree Type"
                                :value="old('config.viral_commission.tree', $defaults['viral_commission']['tree'])"
                                required
                                :options="['enrollment' => 'Enrollment Tree — Based on who recruited whom', 'placement' => 'Placement Tree — Based on organizational placement']"
                            />
                            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Which tree structure to use for calculating network depth and volume.</p>
                        </div>
                    </div>

                    {{-- Viral Commission Tiers --}}
                    <div class="mt-6"
                        x-data="{
                            tiers: {{ Js::from(old('config.viral_commission.tiers', $defaults['viral_commission']['tiers'])) }},
                            addTier() {
                                this.tiers.push({ min_active_customers: 2, min_referred_volume: 0, min_qvv: 0, daily_reward: 0 });
                            },
                            removeTier(index) {
                                if (this.tiers.length > 1) this.tiers.splice(index, 1);
                            }
                        }"
                    >
                        <div class="mb-3">
                            <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-200">Viral Commission Tiers</h4>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Define fixed daily rewards based on network qualification thresholds. Affiliates qualify for the highest tier they meet all criteria for.</p>
                        </div>

                        <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                <thead class="bg-slate-50 dark:bg-slate-800">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Tier</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                            Min Active Customers
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                            Min Referred Volume ($)
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                            Min QVV ($)
                                            <span class="block font-normal normal-case text-slate-400 dark:text-slate-500">Qualifying Viral Volume</span>
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                            Daily Reward ($)
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                            <span class="sr-only">Actions</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white dark:divide-slate-800 dark:bg-slate-900">
                                    <template x-for="(tier, index) in tiers" :key="index">
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400">
                                                <span x-text="index + 1" class="inline-flex size-7 items-center justify-center rounded-full bg-violet-100 text-xs font-semibold text-violet-700 dark:bg-violet-900/40 dark:text-violet-300"></span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <input
                                                    type="number"
                                                    :name="`config[viral_commission][tiers][${index}][min_active_customers]`"
                                                    x-model="tier.min_active_customers"
                                                    min="0"
                                                    step="1"
                                                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                                                    required
                                                >
                                            </td>
                                            <td class="px-4 py-3">
                                                <input
                                                    type="number"
                                                    :name="`config[viral_commission][tiers][${index}][min_referred_volume]`"
                                                    x-model="tier.min_referred_volume"
                                                    min="0"
                                                    step="0.01"
                                                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                                                    required
                                                >
                                            </td>
                                            <td class="px-4 py-3">
                                                <input
                                                    type="number"
                                                    :name="`config[viral_commission][tiers][${index}][min_qvv]`"
                                                    x-model="tier.min_qvv"
                                                    min="0"
                                                    step="0.01"
                                                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                                                    required
                                                >
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="relative">
                                                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-slate-400">$</span>
                                                    <input
                                                        type="number"
                                                        :name="`config[viral_commission][tiers][${index}][daily_reward]`"
                                                        x-model="tier.daily_reward"
                                                        min="0"
                                                        step="0.01"
                                                        class="block w-full rounded-lg border border-slate-300 bg-white py-2 pl-7 pr-3 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                                                        required
                                                    >
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <button
                                                    type="button"
                                                    @click="removeTier(index)"
                                                    :disabled="tiers.length === 1"
                                                    :class="tiers.length === 1 ? 'cursor-not-allowed opacity-30' : 'hover:text-rose-600 dark:hover:text-rose-400'"
                                                    class="inline-flex size-8 cursor-pointer items-center justify-center rounded-lg text-slate-400 transition-colors focus:outline-none focus:ring-2 focus:ring-rose-500"
                                                    aria-label="Remove tier"
                                                >
                                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Min QVV: Minimum Qualifying Viral Volume needed. QVV balances large and small legs in your network. Daily Reward: fixed dollar amount earned per day when qualified.</p>

                        <button
                            type="button"
                            @click="addTier()"
                            class="mt-3 inline-flex cursor-pointer items-center gap-2 rounded-lg border border-dashed border-violet-300 px-4 py-2 text-sm font-medium text-violet-600 transition-colors hover:border-violet-400 hover:bg-violet-50 focus:outline-none focus:ring-2 focus:ring-violet-500 dark:border-violet-700 dark:text-violet-400 dark:hover:border-violet-600 dark:hover:bg-violet-900/20"
                        >
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            Add Tier
                        </button>
                    </div>

                </x-admin.form-section>
            </div>

            {{-- ================================================================
                 SECTION 6: Payout Caps & Limits
            ================================================================ --}}
            <div class="mt-6">
                <x-admin.form-section title="Payout Caps &amp; Limits" description="Set limits on total commissions to protect company profitability.">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                        <div>
                            <label for="config_caps_total_payout_cap_percent" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                Total Payout Cap (%) <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative mt-1.5">
                                <input
                                    type="number"
                                    name="config[caps][total_payout_cap_percent]"
                                    id="config_caps_total_payout_cap_percent"
                                    value="{{ old('config.caps.total_payout_cap_percent', $defaults['caps']['total_payout_cap_percent']) }}"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    required
                                    class="block w-full rounded-lg border border-slate-300 bg-white py-2 pl-3 pr-8 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                                >
                                <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">%</span>
                            </div>
                            @error('config.caps.total_payout_cap_percent')
                                <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Maximum percentage of total company volume that can be paid out as commissions. For example, 35 means no more than 35% of revenue goes to commissions.</p>
                        </div>

                        <div>
                            <x-admin.select
                                name="config[caps][total_payout_cap_enforcement]"
                                label="Cap Enforcement Method"
                                :value="old('config.caps.total_payout_cap_enforcement', $defaults['caps']['total_payout_cap_enforcement'])"
                                required
                                :options="['proportional_reduction' => 'Proportional Reduction — Reduce all payouts proportionally', 'hard_cap' => 'Hard Cap — Stop paying once limit is reached']"
                            />
                            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">How to handle payouts when the cap is exceeded.</p>
                        </div>

                        <div>
                            <label for="config_caps_viral_cap_percent" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                Viral Commission Cap (%) <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative mt-1.5">
                                <input
                                    type="number"
                                    name="config[caps][viral_cap_percent]"
                                    id="config_caps_viral_cap_percent"
                                    value="{{ old('config.caps.viral_cap_percent', $defaults['caps']['viral_cap_percent']) }}"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    required
                                    class="block w-full rounded-lg border border-slate-300 bg-white py-2 pl-3 pr-8 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                                >
                                <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">%</span>
                            </div>
                            @error('config.caps.viral_cap_percent')
                                <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Maximum percentage of company volume allocated to viral/network commissions specifically.</p>
                        </div>

                        <div>
                            <x-admin.select
                                name="config[caps][viral_cap_enforcement]"
                                label="Viral Cap Enforcement"
                                :value="old('config.caps.viral_cap_enforcement', $defaults['caps']['viral_cap_enforcement'])"
                                required
                                :options="['daily_reduction' => 'Daily Reduction — Adjust daily if trending over cap', 'weekly_reduction' => 'Weekly Reduction']"
                            />
                            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">How viral commission overage is handled.</p>
                        </div>

                        <div class="sm:col-span-2">
                            <x-admin.select
                                name="config[caps][enforcement_order]"
                                label="Enforcement Order"
                                :value="old('config.caps.enforcement_order', $defaults['caps']['enforcement_order'])"
                                required
                                :options="['viral_first' => 'Viral Cap First, Then Global Cap', 'global_first' => 'Global Cap First, Then Viral Cap']"
                            />
                            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Which cap is applied first when both limits are in play.</p>
                        </div>

                    </div>
                </x-admin.form-section>
            </div>

            {{-- ================================================================
                 SECTION 7: Wallet Settings
            ================================================================ --}}
            <div class="mt-6">
                <x-admin.form-section title="Wallet Settings" description="Configure how earned commissions are held and released to affiliates.">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                        <div>
                            <x-admin.select
                                name="config[wallet][credit_timing]"
                                label="Credit Timing"
                                :value="old('config.wallet.credit_timing', $defaults['wallet']['credit_timing'])"
                                required
                                :options="['weekly' => 'Weekly', 'monthly' => 'Monthly']"
                            />
                            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">How often pending commissions are moved into affiliate wallets.</p>
                        </div>

                        <div>
                            <x-admin.input
                                name="config[wallet][release_delay_days]"
                                label="Release Delay (Days)"
                                type="number"
                                step="1"
                                :value="old('config.wallet.release_delay_days', $defaults['wallet']['release_delay_days'])"
                                required
                                placeholder="0"
                            />
                            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Number of days to hold commissions before they become available for withdrawal. Use this for refund protection.</p>
                        </div>

                        <div>
                            <label for="config_wallet_minimum_withdrawal" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                Minimum Withdrawal ($) <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative mt-1.5">
                                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-slate-400">$</span>
                                <input
                                    type="number"
                                    name="config[wallet][minimum_withdrawal]"
                                    id="config_wallet_minimum_withdrawal"
                                    value="{{ old('config.wallet.minimum_withdrawal', $defaults['wallet']['minimum_withdrawal']) }}"
                                    min="0"
                                    step="0.01"
                                    required
                                    class="block w-full rounded-lg border border-slate-300 bg-white py-2 pl-7 pr-3 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                                >
                            </div>
                            @error('config.wallet.minimum_withdrawal')
                                <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">The minimum wallet balance required before an affiliate can request a withdrawal.</p>
                        </div>

                        <div>
                            <x-admin.input
                                name="config[wallet][clawback_window_days]"
                                label="Clawback Window (Days)"
                                type="number"
                                step="1"
                                :value="old('config.wallet.clawback_window_days', $defaults['wallet']['clawback_window_days'])"
                                required
                                placeholder="30"
                            />
                            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Number of days after crediting during which commissions can be reclaimed if the underlying order is refunded or cancelled.</p>
                        </div>

                    </div>
                </x-admin.form-section>
            </div>

            {{-- Submit --}}
            <div class="mt-6 flex items-center justify-end gap-3">
                <a href="{{ route('admin.compensation-plans.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                    Cancel
                </a>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900">
                    Create Plan
                </button>
            </div>

        </form>
    </div>
</x-admin-layout>
