<?php

namespace App\Livewire\Admin\Reports;

use App\Models\User;
use App\Scopes\CompanyScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

class AffiliateActivityReport extends ReportBase
{
    #[Computed]
    public function affiliates(): Collection
    {
        // All affiliates for this company
        $allAffiliates = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->companyId)
            ->where('role', 'affiliate')
            ->select('id', 'name', 'status', 'enrolled_at')
            ->get();

        if ($allAffiliates->isEmpty()) {
            return collect();
        }

        $affiliateIds = $allAffiliates->pluck('id')->all();

        // Order counts in period
        $orderCounts = DB::table('transactions')
            ->where('company_id', $this->companyId)
            ->whereIn('user_id', $affiliateIds)
            ->whereIn('type', ['purchase', 'smartship'])
            ->where('status', 'confirmed')
            ->whereBetween('transaction_date', [$this->startDate, $this->endDate])
            ->groupBy('user_id')
            ->select('user_id', DB::raw('COUNT(*) as order_count'), DB::raw('SUM(xp) as total_xp'))
            ->get()
            ->keyBy('user_id');

        // Earnings in period
        $commEarnings = DB::table('commission_ledger_entries')
            ->join('commission_runs', 'commission_runs.id', '=', 'commission_ledger_entries.commission_run_id')
            ->where('commission_ledger_entries.company_id', $this->companyId)
            ->whereIn('commission_ledger_entries.user_id', $affiliateIds)
            ->whereBetween('commission_runs.run_date', [$this->startDate, $this->endDate])
            ->groupBy('commission_ledger_entries.user_id')
            ->select('commission_ledger_entries.user_id', DB::raw('SUM(commission_ledger_entries.amount) as earned'))
            ->get()
            ->keyBy('user_id');

        return $allAffiliates->map(fn ($affiliate) => [
            'id'          => $affiliate->id,
            'name'        => $affiliate->name,
            'status'      => $affiliate->status,
            'enrolled_at' => $affiliate->enrolled_at,
            'order_count' => (int) ($orderCounts->get($affiliate->id)?->order_count ?? 0),
            'total_xp'    => (string) ($orderCounts->get($affiliate->id)?->total_xp ?? '0'),
            'earned'      => bcadd((string)($commEarnings->get($affiliate->id)?->earned ?? '0'), '0', 2),
        ])->sortByDesc('earned')->values();
    }

    #[Computed]
    public function summary(): array
    {
        $all     = $this->affiliates;
        $active  = $all->where('order_count', '>', 0)->count();
        $earning = $all->filter(fn ($a) => bccomp($a['earned'], '0', 2) > 0)->count();

        return [
            'total'   => $all->count(),
            'active'  => $active,
            'earning' => $earning,
            'passive' => $all->count() - $active,
        ];
    }

    public function render()
    {
        return view('livewire.admin.reports.affiliate-activity-report');
    }
}
