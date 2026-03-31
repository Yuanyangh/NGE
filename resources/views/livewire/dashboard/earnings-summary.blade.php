<div class="bg-white rounded-xl shadow-sm p-6">
    {{-- Section header --}}
    <div class="flex items-center gap-2 mb-5">
        <svg class="h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
        </svg>
        <h2 class="text-lg font-semibold text-gray-900">Earnings Overview</h2>
    </div>

    {{-- Stat cards grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {{-- Total Earned --}}
        <div class="bg-green-50 rounded-xl border-t-4 border-green-400 p-4">
            <div class="flex items-start gap-3">
                <div class="h-10 w-10 rounded-lg bg-green-100 flex items-center justify-center flex-shrink-0">
                    <svg class="h-5 w-5 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm text-gray-500">Total Earned (30d)</p>
                    <p class="text-2xl font-bold text-gray-900 mt-0.5">${{ number_format((float) $totalEarned30d, 2) }}</p>
                </div>
            </div>
        </div>

        {{-- Pending --}}
        <div class="bg-amber-50 rounded-xl border-t-4 border-amber-400 p-4">
            <div class="flex items-start gap-3">
                <div class="h-10 w-10 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
                    <svg class="h-5 w-5 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm text-gray-500">Pending</p>
                    <p class="text-2xl font-bold text-gray-900 mt-0.5">${{ number_format((float) $pendingAmount, 2) }}</p>
                </div>
            </div>
        </div>

        {{-- Wallet Balance --}}
        <div class="bg-indigo-50 rounded-xl border-t-4 border-indigo-400 p-4">
            <div class="flex items-start gap-3">
                <div class="h-10 w-10 rounded-lg bg-indigo-100 flex items-center justify-center flex-shrink-0">
                    <svg class="h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm text-gray-500">Wallet Balance</p>
                    <p class="text-2xl font-bold text-gray-900 mt-0.5">${{ number_format((float) $walletBalance, 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Commission info banner --}}
    <div class="bg-gray-50 rounded-lg p-4 mt-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 text-gray-400 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0c1.1.128 1.907 1.077 1.907 2.185Z" />
                </svg>
                <span class="text-sm text-gray-500">Affiliate Commission Tier:</span>
                <span class="text-sm font-semibold text-gray-900">
                    @if($currentAffiliateTier)
                        Tier {{ $currentAffiliateTier }} ({{ number_format($currentAffiliateRate * 100, 0) }}%)
                    @else
                        Not qualified
                    @endif
                </span>
            </div>
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 text-gray-400 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                </svg>
                <span class="text-sm text-gray-500">Viral Tier:</span>
                <span class="text-sm font-semibold text-gray-900">
                    @if($currentViralTier)
                        Tier {{ $currentViralTier }} (${{ number_format($currentViralDailyReward, 2) }}/day)
                    @else
                        Not qualified
                    @endif
                </span>
            </div>
        </div>
    </div>
</div>
