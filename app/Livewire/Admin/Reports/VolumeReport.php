<?php

namespace App\Livewire\Admin\Reports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

class VolumeReport extends ReportBase
{
    #[Computed]
    public function dailyVolume(): Collection
    {
        return DB::table('transactions')
            ->where('company_id', $this->companyId)
            ->whereBetween('transaction_date', [$this->startDate, $this->endDate])
            ->where('status', 'confirmed')
            ->where('type', '!=', 'refund')
            ->groupBy('transaction_date')
            ->orderBy('transaction_date')
            ->select('transaction_date as date', DB::raw('COALESCE(SUM(xp),0) as xp'))
            ->get()
            ->map(fn ($r) => ['date' => $r->date, 'xp' => (string) $r->xp]);
    }

    #[Computed]
    public function topVolume(): Collection
    {
        return DB::table('transactions')
            ->join('users', 'users.id', '=', 'transactions.user_id')
            ->where('transactions.company_id', $this->companyId)
            ->whereBetween('transaction_date', [$this->startDate, $this->endDate])
            ->where('transactions.status', 'confirmed')
            ->where('transactions.type', '!=', 'refund')
            ->where('transactions.qualifies_for_commission', true)
            ->groupBy('transactions.user_id', 'users.name')
            ->orderByDesc(DB::raw('SUM(transactions.xp)'))
            ->select('transactions.user_id', 'users.name', DB::raw('SUM(transactions.xp) as total_xp'))
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'user_id'   => $r->user_id,
                'name'      => $r->name,
                'total_xp'  => (string) $r->total_xp,
            ]);
    }

    #[Computed]
    public function summary(): array
    {
        $agg = DB::table('transactions')
            ->where('company_id', $this->companyId)
            ->whereBetween('transaction_date', [$this->startDate, $this->endDate])
            ->where('status', 'confirmed')
            ->where('type', '!=', 'refund')
            ->selectRaw('COUNT(*) as tx_count')
            ->selectRaw('COALESCE(SUM(xp),0) as total_xp')
            ->selectRaw('COALESCE(SUM(CASE WHEN qualifies_for_commission = 1 THEN xp ELSE 0 END),0) as qualifying_xp')
            ->first();

        return [
            'tx_count'      => (int) ($agg->tx_count ?? 0),
            'total_xp'      => (string) ($agg->total_xp ?? '0'),
            'qualifying_xp' => (string) ($agg->qualifying_xp ?? '0'),
        ];
    }

    public function render()
    {
        return view('livewire.admin.reports.volume-report');
    }
}
