<x-admin-layout title="{{ $walletAccount->user?->name ?? 'Wallet' }} - Wallet">
    <x-admin.page-header :title="($walletAccount->user?->name ?? 'Unknown') . ' - Wallet Account'" description="Wallet details and movement history.">
        <x-slot:actions>
            <a href="{{ route('admin.wallets.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                Back
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="mt-6 space-y-6">
        {{-- Account Details --}}
        <x-admin.form-section title="Account Details">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Company</dt>
                    <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ $walletAccount->company?->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">User</dt>
                    <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ $walletAccount->user?->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Email</dt>
                    <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ $walletAccount->user?->email ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Currency</dt>
                    <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ $walletAccount->currency ?? 'USD' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Balance (Non-Reversed)</dt>
                    <dd class="mt-1">
                        <x-admin.money :amount="$walletAccount->totalNonReversed()" :decimals="4" class="text-base" />
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Available Balance</dt>
                    <dd class="mt-1">
                        <x-admin.money :amount="$walletAccount->availableBalance()" :decimals="4" class="text-base" />
                    </dd>
                </div>
            </dl>
        </x-admin.form-section>

        {{-- Movement History --}}
        <div>
            <h3 class="mb-4 text-base font-semibold text-slate-900 dark:text-white">Movement History</h3>
            <livewire:admin.tables.wallet-movements-table :wallet-account-id="$walletAccount->id" />
        </div>
    </div>
</x-admin-layout>
