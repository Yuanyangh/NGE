<?php

namespace App\Livewire\Commissions;

use App\Models\CommissionLedgerEntry;
use App\Scopes\CompanyScope;
use Carbon\Carbon;
use Livewire\Component;

class CommissionBreakdown extends Component
{
    public string $affiliateTotal = '0';
    public string $viralTotal = '0';
    public string $adjustmentTotal = '0';
    public string $grandTotal = '0';

    public function mount(): void
    {
        $user = auth()->user();
        $windowStart = Carbon::today()->subDays(29);

        $totals = CommissionLedgerEntry::withoutGlobalScope(CompanyScope::class)
            ->where('user_id', $user->id)
            ->where('company_id', $user->company_id)
            ->whereDate('created_at', '>=', $windowStart->toDateString())
            ->whereDate('created_at', '<=', Carbon::today()->toDateString())
            ->selectRaw("
                SUM(CASE WHEN type = 'affiliate_commission' THEN amount ELSE 0 END) as affiliate_total,
                SUM(CASE WHEN type = 'viral_commission' THEN amount ELSE 0 END) as viral_total,
                SUM(CASE WHEN type IN ('cap_adjustment', 'manual_adjustment') THEN amount ELSE 0 END) as adjustment_total,
                SUM(amount) as grand_total
            ")
            ->first();

        $this->affiliateTotal = (string) ($totals->affiliate_total ?? 0);
        $this->viralTotal = (string) ($totals->viral_total ?? 0);
        $this->adjustmentTotal = (string) ($totals->adjustment_total ?? 0);
        $this->grandTotal = (string) ($totals->grand_total ?? 0);
    }

    public function render()
    {
        return view('livewire.commissions.commission-breakdown');
    }
}
