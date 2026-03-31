<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="flex items-center gap-2 mb-6">
        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3" />
        </svg>
        <h3 class="text-lg font-semibold text-gray-900">Wallet Overview</h3>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Available Balance (hero stat) --}}
        <div class="bg-green-50 rounded-xl p-4 border-t-4 border-green-400">
            <div class="h-10 w-10 rounded-lg bg-green-100 flex items-center justify-center text-green-600 mb-3">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                </svg>
            </div>
            <div class="text-sm text-gray-500">Available Balance</div>
            <div class="text-3xl font-bold text-gray-900 mt-1">${{ number_format((float) $availableBalance, 2) }}</div>
        </div>

        {{-- Pending Credits --}}
        <div class="bg-amber-50 rounded-xl p-4 border-t-4 border-amber-400">
            <div class="h-10 w-10 rounded-lg bg-amber-100 flex items-center justify-center text-amber-600 mb-3">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="text-sm text-gray-500">Pending Credits</div>
            <div class="text-2xl font-bold text-gray-900 mt-1">${{ number_format((float) $pendingCredits, 2) }}</div>
        </div>

        {{-- Total Earned --}}
        <div class="bg-indigo-50 rounded-xl p-4 border-t-4 border-indigo-400">
            <div class="h-10 w-10 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600 mb-3">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                </svg>
            </div>
            <div class="text-sm text-gray-500">Total Earned</div>
            <div class="text-2xl font-bold text-gray-900 mt-1">${{ number_format((float) $totalEarned, 2) }}</div>
        </div>

        {{-- Total Withdrawn --}}
        <div class="bg-gray-50 rounded-xl p-4 border-t-4 border-gray-400">
            <div class="h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center text-gray-600 mb-3">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75H6.912a2.25 2.25 0 00-2.15 1.588L2.35 13.177a2.25 2.25 0 00-.1.661V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 00-2.15-1.588H15M2.25 13.5h3.86a2.25 2.25 0 012.012 1.244l.256.512a2.25 2.25 0 002.013 1.244h3.218a2.25 2.25 0 002.013-1.244l.256-.512a2.25 2.25 0 012.013-1.244h3.859M12 3v8.25m0 0l-3-3m3 3l3-3" />
                </svg>
            </div>
            <div class="text-sm text-gray-500">Total Withdrawn</div>
            <div class="text-2xl font-bold text-gray-900 mt-1">${{ number_format((float) $totalWithdrawn, 2) }}</div>
        </div>
    </div>
</div>
