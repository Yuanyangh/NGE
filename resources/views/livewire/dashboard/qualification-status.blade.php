<div class="bg-white rounded-xl shadow-sm p-6">
    {{-- Section header --}}
    <div class="flex items-center gap-2 mb-5">
        <svg class="h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
        </svg>
        <h2 class="text-lg font-semibold text-gray-900">Qualification Status</h2>
    </div>

    @if($qualification)
        {{-- Prominent status badge --}}
        <div class="mb-5">
            @if($qualification['is_qualified'])
                <div class="inline-flex items-center gap-2 bg-green-50 text-green-700 border border-green-200 rounded-xl px-4 py-2">
                    <svg class="h-5 w-5 text-green-500 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span class="font-semibold">Qualified</span>
                    <span class="text-green-600 text-sm font-normal">— eligible for commissions this period</span>
                </div>
            @else
                <div class="inline-flex items-center gap-2 bg-red-50 text-red-700 border border-red-200 rounded-xl px-4 py-2">
                    <svg class="h-5 w-5 text-red-500 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span class="font-semibold">Not Qualified</span>
                    <span class="text-red-600 text-sm font-normal">— requirements not met</span>
                </div>
            @endif
        </div>

        {{-- Stats mini-cards --}}
        <div class="grid grid-cols-2 gap-3">
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-500 mb-1">Active Customers</p>
                <p class="text-xl font-bold text-gray-900">{{ $qualification['active_customer_count'] }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-500 mb-1">Referred Volume (30d)</p>
                <p class="text-xl font-bold text-gray-900">{{ number_format((float) $qualification['referred_volume_30d'], 0) }} <span class="text-sm font-normal text-gray-500">XP</span></p>
            </div>
        </div>

        {{-- Reasons / details --}}
        @if(! empty($qualification['reasons']))
        <div class="mt-4 space-y-2">
            @foreach($qualification['reasons'] as $reason)
                <div class="flex items-start gap-2 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">
                    <svg class="h-4 w-4 text-amber-500 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                    <span class="text-sm text-amber-800">{{ $reason }}</span>
                </div>
            @endforeach
        </div>
        @endif
    @else
        <div class="flex items-center justify-center py-8 text-gray-400">
            <svg class="h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
            </svg>
            <span class="text-sm">No active compensation plan found.</span>
        </div>
    @endif
</div>
