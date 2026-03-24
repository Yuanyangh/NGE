<div wire:poll.30s>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-admin.stat-card
            label="Total Companies"
            :value="number_format($totalCompanies)"
            icon="building-office-2"
            color="indigo"
        />
        <x-admin.stat-card
            label="Active Affiliates"
            :value="number_format($activeAffiliates)"
            icon="users"
            color="emerald"
        />
        <x-admin.stat-card
            label="Commission Runs (30d)"
            :value="number_format($completedRuns)"
            icon="bolt"
            color="amber"
        />
        <x-admin.stat-card
            label="Total Paid (30d)"
            :value="'$' . number_format($totalPaid, 2)"
            icon="banknotes"
            color="emerald"
        />
    </div>
</div>
