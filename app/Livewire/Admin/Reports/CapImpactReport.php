<?php

namespace App\Livewire\Admin\Reports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

class CapImpactReport extends ReportBase
{
    #[Computed]
    public function runs(): Collection
    {
        return DB::table('commission_runs')
            ->where('company_id', $this->companyId)
            ->whereBetween('run_date', [$this->startDate, $this->endDate])
            ->where('status', 'completed')
            ->orderByDesc('run_date')
            ->select('run_date', 'total_affiliate_commission', 'total_viral_commission', 'viral_cap_triggered')
            ->get()
            ->map(fn ($r) => [
                'run_date'            => $r->run_date,
                'aff_comm'            => bcadd((string)($r->total_affiliate_commission ?? '0'), '0', 2),
                'viral_comm'          => bcadd((string)($r->total_viral_commission ?? '0'), '0', 2),
                'viral_cap_triggered' => (bool) $r->viral_cap_triggered,
            ]);
    }

    #[Computed]
    public function capAdjustments(): Collection
    {
        return DB::table('commission_ledger_entries')
            ->join('commission_runs', 'commission_runs.id', '=', 'commission_ledger_entries.commission_run_id')
            ->join('users', 'users.id', '=', 'commission_ledger_entries.user_id')
            ->where('commission_ledger_entries.company_id', $this->companyId)
            ->where('commission_ledger_entries.type', 'cap_adjustment')
            ->whereBetween('commission_runs.run_date', [$this->startDate, $this->endDate])
            ->groupBy('commission_ledger_entries.user_id', 'users.name')
            ->orderByDesc(DB::raw('SUM(ABS(commission_ledger_entries.amount))'))
            ->select(
                'users.name',
                DB::raw('SUM(ABS(commission_ledger_entries.amount)) as total_reduction'),
                DB::raw('COUNT(*) as adjustment_count'),
            )
            ->limit(20)
            ->get()
            ->map(fn ($r) => [
                'name'            => $r->name,
                'total_reduction' => bcadd((string)$r->total_reduction, '0', 2),
                'count'           => (int) $r->adjustment_count,
            ]);
    }

    #[Computed]
    public function summary(): array
    {
        $agg = DB::table('commission_runs')
            ->where('company_id', $this->companyId)
            ->whereBetween('run_date', [$this->startDate, $this->endDate])
            ->where('status', 'completed')
            ->selectRaw('SUM(CASE WHEN viral_cap_triggered = 1 THEN 1 ELSE 0 END) as viral_triggers')
            ->selectRaw('COUNT(*) as total_runs')
            ->first();

        $capTotal = DB::table('commission_ledger_entries')
            ->join('commission_runs', 'commission_runs.id', '=', 'commission_ledger_entries.commission_run_id')
            ->where('commission_ledger_entries.company_id', $this->companyId)
            ->where('commission_ledger_entries.type', 'cap_adjustment')
            ->whereBetween('commission_runs.run_date', [$this->startDate, $this->endDate])
            ->sum(DB::raw('ABS(commission_ledger_entries.amount)'));

        return [
            'viral_triggers' => (int) ($agg->viral_triggers ?? 0),
            'total_runs'     => (int) ($agg->total_runs ?? 0),
            'cap_reduction'  => bcadd((string)$capTotal, '0', 2),
        ];
    }

    public function render()
    {
        return view('livewire.admin.reports.cap-impact-report');
    }
}
