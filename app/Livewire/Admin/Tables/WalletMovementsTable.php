<?php

namespace App\Livewire\Admin\Tables;

use App\Models\WalletMovement;
use App\Scopes\CompanyScope;
use Livewire\Component;
use Livewire\WithPagination;

class WalletMovementsTable extends Component
{
    use WithPagination;

    public int $walletAccountId;

    public function render()
    {
        $movements = WalletMovement::withoutGlobalScope(CompanyScope::class)
            ->where('wallet_account_id', $this->walletAccountId)
            ->orderByDesc('effective_at')
            ->paginate(25);

        return view('livewire.admin.tables.wallet-movements-table', [
            'movements' => $movements,
        ]);
    }
}
