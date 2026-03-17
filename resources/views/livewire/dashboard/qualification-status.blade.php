<div class="bg-white overflow-hidden shadow rounded-lg p-5">
    <h3 class="text-lg font-medium text-gray-900">Qualification Status</h3>

    @if($qualification)
    <div class="mt-3 space-y-2">
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $qualification['is_qualified'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                {{ $qualification['is_qualified'] ? 'Qualified' : 'Not Qualified' }}
            </span>
        </div>

        <div class="grid grid-cols-2 gap-4 mt-3">
            <div>
                <div class="text-sm text-gray-500">Active Customers</div>
                <div class="text-xl font-semibold">{{ $qualification['active_customer_count'] }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Referred Volume (30d)</div>
                <div class="text-xl font-semibold">{{ number_format((float) $qualification['referred_volume_30d'], 0) }} XP</div>
            </div>
        </div>

        @if(! empty($qualification['reasons']))
        <div class="mt-3 border-t pt-3">
            <div class="text-sm text-gray-500">Details</div>
            <ul class="mt-1 text-sm text-gray-700 space-y-1">
                @foreach($qualification['reasons'] as $reason)
                    <li>{{ $reason }}</li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
    @else
        <p class="mt-2 text-gray-500">No active compensation plan found.</p>
    @endif
</div>
