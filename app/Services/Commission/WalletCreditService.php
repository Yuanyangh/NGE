<?php

namespace App\Services\Commission;

use App\Models\CommissionLedgerEntry;
use App\Models\Company;
use App\Scopes\CompanyScope;
use App\Models\WalletAccount;
use App\Models\WalletMovement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletCreditService
{
    /**
     * Credit wallet accounts from approved commission ledger entries.
     *
     * Finds all commission entries that haven't been credited to wallets yet
     * and creates wallet movements for them.
     */
    public function creditFromCommissions(Company $company, Carbon $date): array
    {
        Log::info("Starting wallet credit for {$company->slug} on {$date->toDateString()}");

        $credited = [];

        DB::transaction(function () use ($company, $date, &$credited) {
            // Get all commission entries that need crediting
            // (affiliate + viral commissions with positive amounts, not yet credited)
            $entries = CommissionLedgerEntry::withoutGlobalScope(CompanyScope::class)
                ->where('company_id', $company->id)
                ->whereIn('type', ['affiliate_commission', 'viral_commission'])
                ->where('amount', '>', 0)
                ->whereDoesntHave('walletMovement')
                ->get();

            foreach ($entries as $entry) {
                // Ensure wallet account exists
                $walletAccount = WalletAccount::withoutGlobalScope(CompanyScope::class)
                    ->firstOrCreate(
                        [
                            'company_id' => $company->id,
                            'user_id' => $entry->user_id,
                        ],
                        [
                            'currency' => $company->currency,
                        ]
                    );

                $movement = WalletMovement::create([
                    'company_id' => $company->id,
                    'wallet_account_id' => $walletAccount->id,
                    'type' => 'commission_credit',
                    'amount' => $entry->amount,
                    'status' => 'approved',
                    'reference_type' => 'commission_ledger_entry',
                    'reference_id' => $entry->id,
                    'description' => sprintf(
                        '%s: %s',
                        $entry->type === 'affiliate_commission' ? 'Affiliate commission' : 'Viral commission',
                        $entry->description ?? ''
                    ),
                    'effective_at' => $date,
                    'created_at' => now(),
                ]);

                $credited[] = [
                    'user_id' => $entry->user_id,
                    'ledger_entry_id' => $entry->id,
                    'movement_id' => $movement->id,
                    'amount' => $entry->amount,
                    'type' => $entry->type,
                ];
            }
        });

        Log::info(sprintf(
            "Wallet credit completed for %s: %d movements created",
            $company->slug,
            count($credited)
        ));

        return $credited;
    }
}
