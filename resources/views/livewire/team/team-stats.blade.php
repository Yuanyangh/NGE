<div class="bg-white overflow-hidden shadow rounded-lg p-5">
    <h3 class="text-lg font-medium text-gray-900">Team Overview</h3>

    @if($stats)
    <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">
        <div>
            <div class="text-sm text-gray-500">Total Team Size</div>
            <div class="text-2xl font-semibold">{{ $stats['total_team_size'] }}</div>
        </div>
        <div>
            <div class="text-sm text-gray-500">Active Affiliates</div>
            <div class="text-2xl font-semibold">{{ $stats['active_affiliates'] }}</div>
        </div>
        <div>
            <div class="text-sm text-gray-500">Active Customers</div>
            <div class="text-2xl font-semibold">{{ $stats['active_customers'] }}</div>
        </div>
        <div>
            <div class="text-sm text-gray-500">Team Volume (30d)</div>
            <div class="text-2xl font-semibold">{{ number_format((float) $stats['total_team_volume_30d'], 0) }} XP</div>
        </div>
    </div>
    @else
        <p class="mt-3 text-sm text-gray-500">No team data available.</p>
    @endif
</div>
