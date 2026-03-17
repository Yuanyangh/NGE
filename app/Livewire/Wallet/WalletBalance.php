<?php

namespace App\Livewire\Wallet;

use App\Models\WalletMovement;
use App\Scopes\CompanyScope;
use Livewire\Component;

class WalletBalance extends Component
{
    public string $availableBalance = '0';
    public string $pendingCredits = '0';
    public string $totalEarned = '0';
    public string $totalWithdrawn = '0';

    public function mount(): void
    {
        $user = auth()->user();
        $walletAccount = $user->walletAccount;

        if (! $walletAccount) {
            return;
        }

        $this->availableBalance = (string) WalletMovement::withoutGlobalScope(CompanyScope::class)
            ->where('wallet_account_id', $walletAccount->id)
            ->where('company_id', $user->company_id)
            ->whereIn('status', ['approved', 'released'])
            ->sum('amount');

        $this->pendingCredits = (string) WalletMovement::withoutGlobalScope(CompanyScope::class)
            ->where('wallet_account_id', $walletAccount->id)
            ->where('company_id', $user->company_id)
            ->where('status', 'pending')
            ->sum('amount');

        $this->totalEarned = (string) WalletMovement::withoutGlobalScope(CompanyScope::class)
            ->where('wallet_account_id', $walletAccount->id)
            ->where('company_id', $user->company_id)
            ->where('type', 'commission_credit')
            ->whereNotIn('status', ['reversed'])
            ->sum('amount');

        $withdrawnSum = (string) WalletMovement::withoutGlobalScope(CompanyScope::class)
            ->where('wallet_account_id', $walletAccount->id)
            ->where('company_id', $user->company_id)
            ->where('type', 'withdrawal')
            ->whereNotIn('status', ['reversed'])
            ->sum('amount');
        // Withdrawals are stored as negative amounts; convert to positive for display
        $this->totalWithdrawn = bccomp($withdrawnSum, '0', 4) < 0
            ? bcmul($withdrawnSum, '-1', 4)
            : $withdrawnSum;
    }

    public function render()
    {
        return view('livewire.wallet.wallet-balance');
    }
}
