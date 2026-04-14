<?php

namespace App\Livewire\Admin\Reports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

class BonusPayoutReport extends ReportBase
{
    #[Computed]
    public function byBonusType(): Collection
    {
        return DB::table('bonus_ledger_entries')
            ->join('commission_runs', 'commission_runs.id', '=', 'bonus_ledger_entries.commission_run_id')
            ->join('bonus_types', 'bonus_types.id', '=', 'bonus_ledger_entries.bonus_type_id')
            ->where('bonus_ledger_entries.company_id', $this->companyId)
            ->whereBetween('commission_runs.run_date', [$this->startDate, $this->endDate])
            ->groupBy('bonus_ledger_entries.bonus_type_id', 'bonus_types.name', 'bonus_types.type')
            ->orderByDesc(DB::raw('SUM(bonus_ledger_entries.amount)'))
            ->select(
                'bonus_types.name',
                'bonus_types.type',
                DB::raw('COUNT(DISTINCT bonus_ledger_entries.user_id) as recipient_count'),
                DB::raw('COUNT(*) as payout_count'),
                DB::raw('SUM(bonus_ledger_entries.amount) as total_amount'),
            )
            ->get()
            ->map(fn ($r) => [
                'name'            => $r->name,
                'type'            => $r->type,
                'recipient_count' => (int) $r->recipient_count,
                'payout_count'    => (int) $r->payout_count,
                'total_amount'    => bcadd((string)$r->total_amount, '0', 2),
            ]);
    }

    #[Computed]
    public function totalBonuses(): string
    {
        $sum = DB::table('bonus_ledger_entries')
            ->join('commission_runs', 'commission_runs.id', '=', 'bonus_ledger_entries.commission_run_id')
            ->where('bonus_ledger_entries.company_id', $this->companyId)
            ->whereBetween('commission_runs.run_date', [$this->startDate, $this->endDate])
            ->sum('bonus_ledger_entries.amount');

        return bcadd((string)$sum, '0', 2);
    }

    public function render()
    {
        return view('livewire.admin.reports.bonus-payout-report');
    }
}
