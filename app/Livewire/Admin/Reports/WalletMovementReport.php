<?php

namespace App\Livewire\Admin\Reports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

class WalletMovementReport extends ReportBase
{
    #[Computed]
    public function movements(): Collection
    {
        return DB::table('wallet_movements')
            ->join('wallet_accounts', 'wallet_accounts.id', '=', 'wallet_movements.wallet_account_id')
            ->join('users', 'users.id', '=', 'wallet_accounts.user_id')
            ->where('wallet_movements.company_id', $this->companyId)
            ->whereBetween('wallet_movements.created_at', [
                $this->start()->toDateTimeString(),
                $this->end()->toDateTimeString(),
            ])
            ->orderByDesc('wallet_movements.created_at')
            ->select(
                'wallet_movements.id',
                'users.name',
                'wallet_movements.type',
                'wallet_movements.amount',
                'wallet_movements.status',
                'wallet_movements.created_at',
            )
            ->limit(200)
            ->get()
            ->map(fn ($r) => [
                'id'         => $r->id,
                'name'       => $r->name,
                'type'       => $r->type,
                'amount'     => (string) $r->amount,
                'status'     => $r->status,
                'created_at' => $r->created_at,
            ]);
    }

    #[Computed]
    public function summary(): array
    {
        $agg = DB::table('wallet_movements')
            ->where('company_id', $this->companyId)
            ->whereBetween('created_at', [
                $this->start()->toDateTimeString(),
                $this->end()->toDateTimeString(),
            ])
            ->selectRaw("SUM(CASE WHEN type IN ('commission_credit','commission_release') THEN amount ELSE 0 END) as total_credits")
            ->selectRaw("SUM(CASE WHEN type = 'clawback' THEN ABS(amount) ELSE 0 END) as total_clawbacks")
            ->selectRaw("SUM(CASE WHEN type = 'withdrawal' THEN ABS(amount) ELSE 0 END) as total_withdrawals")
            ->selectRaw('COUNT(*) as movement_count')
            ->first();

        return [
            'credits'     => bcadd((string)($agg->total_credits ?? '0'), '0', 2),
            'clawbacks'   => bcadd((string)($agg->total_clawbacks ?? '0'), '0', 2),
            'withdrawals' => bcadd((string)($agg->total_withdrawals ?? '0'), '0', 2),
            'count'       => (int) ($agg->movement_count ?? 0),
        ];
    }

    public function render()
    {
        return view('livewire.admin.reports.wallet-movement-report');
    }
}
