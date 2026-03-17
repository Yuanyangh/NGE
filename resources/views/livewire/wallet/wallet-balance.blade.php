<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div class="bg-white overflow-hidden shadow rounded-lg p-5">
        <div class="text-sm text-gray-500">Available Balance</div>
        <div class="mt-1 text-2xl font-semibold text-green-600">${{ number_format((float) $availableBalance, 2) }}</div>
    </div>
    <div class="bg-white overflow-hidden shadow rounded-lg p-5">
        <div class="text-sm text-gray-500">Pending Credits</div>
        <div class="mt-1 text-2xl font-semibold text-yellow-600">${{ number_format((float) $pendingCredits, 2) }}</div>
    </div>
    <div class="bg-white overflow-hidden shadow rounded-lg p-5">
        <div class="text-sm text-gray-500">Total Earned</div>
        <div class="mt-1 text-2xl font-semibold text-gray-900">${{ number_format((float) $totalEarned, 2) }}</div>
    </div>
    <div class="bg-white overflow-hidden shadow rounded-lg p-5">
        <div class="text-sm text-gray-500">Total Withdrawn</div>
        <div class="mt-1 text-2xl font-semibold text-gray-900">${{ number_format((float) $totalWithdrawn, 2) }}</div>
    </div>
</div>
