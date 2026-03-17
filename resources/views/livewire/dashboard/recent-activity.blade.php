<div class="bg-white overflow-hidden shadow rounded-lg">
    <div class="p-5">
        <h3 class="text-lg font-medium text-gray-900">Recent Activity</h3>

        @if(empty($entries))
            <p class="mt-3 text-sm text-gray-500">No commission activity yet.</p>
        @else
            <div class="mt-3 flow-root">
                <ul class="-my-4 divide-y divide-gray-200">
                    @foreach($entries as $entry)
                    <li class="flex items-center justify-between py-4">
                        <div>
                            <p class="text-sm font-medium text-gray-900">
                                {{ str_replace('_', ' ', ucfirst($entry['type'])) }}
                            </p>
                            <p class="text-xs text-gray-500">{{ $entry['created_at'] }}</p>
                        </div>
                        <span class="text-sm font-semibold {{ (float) $entry['amount'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ (float) $entry['amount'] >= 0 ? '+' : '' }}${{ number_format((float) $entry['amount'], 2) }}
                        </span>
                    </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
