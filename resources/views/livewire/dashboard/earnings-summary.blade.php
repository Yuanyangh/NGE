<div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="text-sm font-medium text-gray-500">Total Earned (30d)</div>
                <div class="mt-1 text-3xl font-semibold text-gray-900">${{ number_format((float) $totalEarned30d, 2) }}</div>
            </div>
        </div>
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="text-sm font-medium text-gray-500">Pending</div>
                <div class="mt-1 text-3xl font-semibold text-yellow-600">${{ number_format((float) $pendingAmount, 2) }}</div>
            </div>
        </div>
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="text-sm font-medium text-gray-500">Wallet Balance</div>
                <div class="mt-1 text-3xl font-semibold text-green-600">${{ number_format((float) $walletBalance, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="mt-4 bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5 flex flex-wrap gap-6">
            <div>
                <span class="text-sm text-gray-500">Affiliate Commission Rate:</span>
                <span class="ml-1 font-semibold text-gray-900">
                    {{ $currentAffiliateRate ? number_format($currentAffiliateRate * 100, 0) . '%' : 'Not qualified' }}
                </span>
            </div>
            <div>
                <span class="text-sm text-gray-500">Viral Tier:</span>
                <span class="ml-1 font-semibold text-gray-900">
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
