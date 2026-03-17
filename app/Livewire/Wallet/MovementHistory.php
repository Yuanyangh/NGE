<?php

namespace App\Livewire\Wallet;

use App\Models\WalletMovement;
use App\Scopes\CompanyScope;
use Livewire\Component;
use Livewire\WithPagination;

class MovementHistory extends Component
{
    use WithPagination;

    public function render()
    {
        $user = auth()->user();
        $walletAccount = $user->walletAccount;

        $movements = collect();
        if ($walletAccount) {
            $movements = WalletMovement::withoutGlobalScope(CompanyScope::class)
                ->where('wallet_account_id', $walletAccount->id)
                ->where('company_id', $user->company_id)
                ->orderByDesc('effective_at')
                ->paginate(15);
        }

        return view('livewire.wallet.movement-history', [
            'movements' => $movements,
        ]);
    }
}
