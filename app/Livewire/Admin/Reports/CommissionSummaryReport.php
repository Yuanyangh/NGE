<?php

namespace App\Livewire\Admin\Reports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

class CommissionSummaryReport extends ReportBase
{
    #[Computed]
    public function runs(): Collection
    {
        return DB::table('commission_runs')
            ->where('company_id', $this->companyId)
            ->whereBetween('run_date', [$this->startDate, $this->endDate])
            ->where('status', 'completed')
            ->orderByDesc('run_date')
            ->get()
            ->map(fn ($r) => [
                'run_date'              => $r->run_date,
                'total_volume'          => (string) ($r->total_company_volume ?? '0'),
                'affiliate_commission'  => (string) ($r->total_affiliate_commission ?? '0'),
                'viral_commission'      => (string) ($r->total_viral_commission ?? '0'),
                'bonuses'               => (string) ($r->total_bonus_amount ?? '0'),
                'total_payout'          => bcadd(
                    bcadd((string)($r->total_affiliate_commission ?? '0'), (string)($r->total_viral_commission ?? '0'), 4),
                    (string)($r->total_bonus_amount ?? '0'),
                    2
                ),
                'viral_cap_triggered'   => (bool) ($r->viral_cap_triggered ?? false),
            ]);
    }

    #[Computed]
    public function totals(): array
    {
        $agg = DB::table('commission_runs')
            ->where('company_id', $this->companyId)
            ->whereBetween('run_date', [$this->startDate, $this->endDate])
            ->where('status', 'completed')
            ->selectRaw('COUNT(*) as run_count')
            ->selectRaw('COALESCE(SUM(total_company_volume),0) as total_volume')
            ->selectRaw('COALESCE(SUM(total_affiliate_commission),0) as aff_comm')
            ->selectRaw('COALESCE(SUM(total_viral_commission),0) as viral_comm')
            ->selectRaw('COALESCE(SUM(total_bonus_amount),0) as bonuses')
            ->first();

        $totalComm = bcadd((string)($agg->aff_comm ?? '0'), (string)($agg->viral_comm ?? '0'), 4);
        $totalPayout = bcadd($totalComm, (string)($agg->bonuses ?? '0'), 2);

        return [
            'run_count'   => (int) ($agg->run_count ?? 0),
            'volume'      => bcadd((string)($agg->total_volume ?? '0'), '0', 2),
            'aff_comm'    => bcadd((string)($agg->aff_comm ?? '0'), '0', 2),
            'viral_comm'  => bcadd((string)($agg->viral_comm ?? '0'), '0', 2),
            'bonuses'     => bcadd((string)($agg->bonuses ?? '0'), '0', 2),
            'total_payout'=> $totalPayout,
        ];
    }

    public function render()
    {
        return view('livewire.admin.reports.commission-summary-report');
    }
}
