<div class="bg-white overflow-hidden shadow rounded-lg p-5">
    <h3 class="text-lg font-medium text-gray-900">Commission Breakdown (30 days)</h3>

    <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">
        <div>
            <div class="text-sm text-gray-500">Affiliate Commissions</div>
            <div class="text-xl font-semibold text-blue-600">${{ number_format((float) $affiliateTotal, 2) }}</div>
        </div>
        <div>
            <div class="text-sm text-gray-500">Viral Commissions</div>
            <div class="text-xl font-semibold text-emerald-600">${{ number_format((float) $viralTotal, 2) }}</div>
        </div>
        <div>
            <div class="text-sm text-gray-500">Adjustments</div>
            <div class="text-xl font-semibold text-gray-600">${{ number_format((float) $adjustmentTotal, 2) }}</div>
        </div>
        <div>
            <div class="text-sm text-gray-500">Total</div>
            <div class="text-xl font-semibold text-gray-900">${{ number_format((float) $grandTotal, 2) }}</div>
        </div>
    </div>
</div>
