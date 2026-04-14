<?php

namespace App\Livewire\Admin\Reports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

class TopEarnersReport extends ReportBase
{
    #[Locked]
    public int $limit = 25;

    #[Computed]
    public function earners(): Collection
    {
        $commissionEarnings = DB::table('commission_ledger_entries')
            ->join('commission_runs', 'commission_runs.id', '=', 'commission_ledger_entries.commission_run_id')
            ->where('commission_ledger_entries.company_id', $this->companyId)
            ->whereBetween('commission_runs.run_date', [$this->startDate, $this->endDate])
            ->groupBy('commission_ledger_entries.user_id')
            ->select('commission_ledger_entries.user_id', DB::raw('SUM(commission_ledger_entries.amount) as earned'))
            ->get()
            ->keyBy('user_id');

        $bonusEarnings = DB::table('bonus_ledger_entries')
            ->join('commission_runs', 'commission_runs.id', '=', 'bonus_ledger_entries.commission_run_id')
            ->where('bonus_ledger_entries.company_id', $this->companyId)
            ->whereBetween('commission_runs.run_date', [$this->startDate, $this->endDate])
            ->groupBy('bonus_ledger_entries.user_id')
            ->select('bonus_ledger_entries.user_id', DB::raw('SUM(bonus_ledger_entries.amount) as earned'))
            ->get()
            ->keyBy('user_id');

        $allUserIds = $commissionEarnings->keys()->merge($bonusEarnings->keys())->unique();

        $merged = $allUserIds->map(function ($userId) use ($commissionEarnings, $bonusEarnings) {
            $comm  = (string) ($commissionEarnings->get($userId)?->earned ?? '0');
            $bonus = (string) ($bonusEarnings->get($userId)?->earned ?? '0');
            return [
                'user_id'     => $userId,
                'comm'        => bcadd($comm, '0', 2),
                'bonus'       => bcadd($bonus, '0', 2),
                'total'       => bcadd($comm, $bonus, 4),
            ];
        })
        ->sortByDesc('total')
        ->take($this->limit)
        ->values();

        if ($merged->isEmpty()) {
            return collect();
        }

        $userNames = DB::table('users')
            ->whereIn('id', $merged->pluck('user_id'))
            ->pluck('name', 'id');

        $maxTotal = $merged->max('total') ?: '1';

        return $merged->map(fn ($row) => [
            'user_id'   => (int) $row['user_id'],
            'name'      => $userNames->get($row['user_id'], 'Unknown'),
            'comm'      => $row['comm'],
            'bonus'     => $row['bonus'],
            'total'     => bcadd($row['total'], '0', 2),
            'bar_width' => (int) round(bcmul(bcdiv($row['total'], $maxTotal, 6), '100', 2)),
        ]);
    }

    public function render()
    {
        return view('livewire.admin.reports.top-earners-report');
    }
}
