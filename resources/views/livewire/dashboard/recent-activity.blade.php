<div class="bg-white rounded-xl shadow-sm p-6">
    {{-- Section header --}}
    <div class="flex items-center gap-2 mb-5">
        <svg class="h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        <h2 class="text-lg font-semibold text-gray-900">Recent Activity</h2>
    </div>

    @if(empty($entries))
        {{-- Empty state --}}
        <div class="flex flex-col items-center justify-center py-10 text-gray-400">
            <svg class="h-10 w-10 mb-3 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
            </svg>
            <p class="text-sm font-medium text-gray-500">No commission activity yet</p>
            <p class="text-xs text-gray-400 mt-1">Earned commissions will appear here</p>
        </div>
    @else
        <ul class="divide-y divide-gray-50">
            @foreach($entries as $entry)
            <li class="flex items-center justify-between py-3 first:pt-0 last:pb-0">
                <div class="flex items-start gap-3 min-w-0">
                    {{-- Type badge --}}
                    @php
                        $typeLabel = str_replace('_', ' ', ucfirst($entry['type']));
                        $isPositive = (float) $entry['amount'] >= 0;
                    @endphp
                    <div class="flex-shrink-0 mt-0.5">
                        @if($isPositive)
                            <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                {{ $typeLabel }}
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20">
                                {{ $typeLabel }}
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5 truncate">{{ $entry['created_at'] }}</p>
                </div>
                <span class="text-sm font-semibold flex-shrink-0 ml-3 {{ $isPositive ? 'text-green-600' : 'text-red-600' }}">
                    {{ $isPositive ? '+' : '' }}${{ number_format((float) $entry['amount'], 2) }}
                </span>
            </li>
            @endforeach
        </ul>
    @endif
</div>
