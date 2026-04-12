<?php

namespace App\Services\Commission;

use App\DTOs\ClawbackResult;
use App\Exceptions\Commission\ClawbackException;
use App\Models\BonusLedgerEntry;
use App\Models\CommissionLedgerEntry;
use App\Models\CommissionRun;
use App\Models\Transaction;
use App\Models\WalletAccount;
use App\Models\WalletMovement;
use App\Scopes\CompanyScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClawbackService
{
    /**
     * Process a clawback for a refund transaction.
     *
     * Finds commissions earned from the original transaction and creates
     * negative wallet movements to claw them back proportionally.
     *
     * @param Transaction $refundTransaction Must be type='refund'
     * @return ClawbackResult Summary of clawbacks applied
     *
     * @throws ClawbackException If the transaction is not a refund or original cannot be found
     */
    public function processRefund(Transaction $refundTransaction): ClawbackResult
    {
        // 1. Validate: transaction must be type 'refund'
        if ($refundTransaction->type !== 'refund') {
            throw new ClawbackException(
                "Transaction #{$refundTransaction->id} is type '{$refundTransaction->type}', expected 'refund'."
            );
        }

        // 2. Find the original transaction via reference field
        $originalTransaction = $this->findOriginalTransaction($refundTransaction);

        if ($originalTransaction === null) {
            throw new ClawbackException(
                "Cannot find original transaction for refund #{$refundTransaction->id} "
                . "(reference: {$refundTransaction->reference})."
            );
        }

        // Idempotency guard: check if this refund has already been processed
        $alreadyClawedBack = WalletMovement::withoutGlobalScope(CompanyScope::class)
            ->where('type', 'clawback')
            ->where('reference_type', 'refund_transaction')
            ->where('reference_id', $refundTransaction->id)
            ->exists();

        if ($alreadyClawedBack) {
            Log::info("Clawback already processed for refund #{$refundTransaction->id} — skipping duplicate");

            return new ClawbackResult(
                user_id: $originalTransaction->referred_by_user_id ?? $originalTransaction->user_id,
                original_transaction_id: $originalTransaction->id,
                refund_transaction_id: $refundTransaction->id,
                total_clawback_amount: '0.0000',
                clawback_details: [],
                is_partial: false,
            );
        }

        Log::info("Processing clawback for refund #{$refundTransaction->id} -> original #{$originalTransaction->id}");

        // 3. Determine if partial or full refund
        $isPartial = bccomp($refundTransaction->xp, $originalTransaction->xp, 2) < 0;

        // Calculate proportional ratio using XP (bcmath)
        // ratio = refund.xp / original.xp (capped at 1.0)
        $ratio = '1';
        if (bccomp($originalTransaction->xp, '0', 2) > 0) {
            $ratio = bcdiv((string) $refundTransaction->xp, (string) $originalTransaction->xp, 10);
            // Cap ratio at 1.0 — never claw back more than was earned
            if (bccomp($ratio, '1', 10) > 0) {
                $ratio = '1';
            }
        }

        // 4. Find commission ledger entries linked to the original transaction's affiliate
        $commissionEntries = $this->findCommissionEntries($originalTransaction);
        $bonusEntries = $this->findBonusEntries($originalTransaction);

        // If no commissions were earned, return empty result
        if ($commissionEntries->isEmpty() && $bonusEntries->isEmpty()) {
            Log::info("No commissions found for original transaction #{$originalTransaction->id} — no clawback needed");

            return new ClawbackResult(
                user_id: $originalTransaction->referred_by_user_id ?? $originalTransaction->user_id,
                original_transaction_id: $originalTransaction->id,
                refund_transaction_id: $refundTransaction->id,
                total_clawback_amount: '0.0000',
                clawback_details: [],
                is_partial: $isPartial,
            );
        }

        // 5. Create clawback wallet movements within a transaction
        $clawbackDetails = [];
        $totalClawback = '0';

        DB::transaction(function () use (
            $commissionEntries,
            $bonusEntries,
            $refundTransaction,
            $originalTransaction,
            $ratio,
            &$clawbackDetails,
            &$totalClawback,
        ) {
            // Find the affiliate's wallet account for the sentinel record
            $affiliateUserId = $originalTransaction->referred_by_user_id ?? $originalTransaction->user_id;
            $sentinelWallet = WalletAccount::withoutGlobalScope(CompanyScope::class)
                ->where('company_id', $refundTransaction->company_id)
                ->where('user_id', $affiliateUserId)
                ->first();

            // Create a zero-amount sentinel movement to mark this refund as processed (idempotency)
            if ($sentinelWallet !== null) {
                WalletMovement::create([
                    'company_id' => $refundTransaction->company_id,
                    'wallet_account_id' => $sentinelWallet->id,
                    'type' => 'clawback',
                    'amount' => '0.0000',
                    'status' => 'approved',
                    'reference_type' => 'refund_transaction',
                    'reference_id' => $refundTransaction->id,
                    'description' => sprintf('Clawback sentinel for refund #%d', $refundTransaction->id),
                    'effective_at' => now(),
                    'created_at' => now(),
                ]);
            }

            // Process commission ledger entries
            foreach ($commissionEntries as $entry) {
                $result = $this->createClawbackMovement(
                    entry: $entry,
                    referenceType: 'commission_ledger_entry',
                    refundTransaction: $refundTransaction,
                    ratio: $ratio,
                );

                if ($result !== null) {
                    $clawbackDetails[] = $result;
                    $totalClawback = bcadd($totalClawback, $result['clawback_amount'], 4);
                }
            }

            // Process bonus ledger entries
            foreach ($bonusEntries as $entry) {
                $result = $this->createClawbackMovement(
                    entry: $entry,
                    referenceType: 'bonus_ledger_entry',
                    refundTransaction: $refundTransaction,
                    ratio: $ratio,
                );

                if ($result !== null) {
                    $clawbackDetails[] = $result;
                    $totalClawback = bcadd($totalClawback, $result['clawback_amount'], 4);
                }
            }
        });

        Log::info(sprintf(
            "Clawback completed for refund #%d: %d entries, total=%s",
            $refundTransaction->id,
            count($clawbackDetails),
            $totalClawback,
        ));

        return new ClawbackResult(
            user_id: $originalTransaction->referred_by_user_id ?? $originalTransaction->user_id,
            original_transaction_id: $originalTransaction->id,
            refund_transaction_id: $refundTransaction->id,
            total_clawback_amount: $totalClawback,
            clawback_details: $clawbackDetails,
            is_partial: $isPartial,
        );
    }

    /**
     * Find the original transaction that this refund references.
     *
     * Matching strategy:
     * 1. If refund.reference is numeric, try matching by original transaction ID
     * 2. Match by reference field (refund.reference == original.reference)
     */
    private function findOriginalTransaction(Transaction $refund): ?Transaction
    {
        $reference = $refund->reference;

        if ($reference === null || $reference === '') {
            return null;
        }

        // Strategy 1: reference is the original transaction's ID
        if (is_numeric($reference)) {
            $byId = Transaction::withoutGlobalScope(CompanyScope::class)
                ->where('company_id', $refund->company_id)
                ->where('id', (int) $reference)
                ->whereIn('type', ['purchase', 'smartship'])
                ->first();

            if ($byId !== null) {
                return $byId;
            }
        }

        // Strategy 2: match by reference field
        return Transaction::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $refund->company_id)
            ->where('reference', $reference)
            ->whereIn('type', ['purchase', 'smartship'])
            ->where('id', '!=', $refund->id)
            ->orderBy('transaction_date', 'asc')
            ->first();
    }

    /**
     * Find commission ledger entries that were generated from the original transaction.
     *
     * Strategy: find commission runs whose run_date covers the original transaction's date
     * (within the 30-day window), then find ledger entries for the referring affiliate.
     */
    private function findCommissionEntries(Transaction $originalTransaction): \Illuminate\Support\Collection
    {
        // The original transaction could have contributed to commission runs
        // from its transaction_date through 29 days after (it stays in the 30-day window).
        $referredByUserId = $originalTransaction->referred_by_user_id;

        if ($referredByUserId === null) {
            return collect();
        }

        $txDate = $originalTransaction->transaction_date;

        // Commission runs where this transaction was within the 30-day window:
        // run_date >= tx_date AND run_date <= tx_date + 29 days
        $runIds = CommissionRun::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $originalTransaction->company_id)
            ->where('status', 'completed')
            ->whereDate('run_date', '>=', $txDate->toDateString())
            ->whereDate('run_date', '<=', $txDate->copy()->addDays(29)->toDateString())
            ->pluck('id');

        if ($runIds->isEmpty()) {
            return collect();
        }

        return CommissionLedgerEntry::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $originalTransaction->company_id)
            ->where('user_id', $referredByUserId)
            ->whereIn('commission_run_id', $runIds)
            ->whereIn('type', ['affiliate_commission', 'viral_commission'])
            ->where('amount', '>', 0)
            ->get();
    }

    /**
     * Find bonus ledger entries generated from commission runs covering the original transaction.
     */
    private function findBonusEntries(Transaction $originalTransaction): \Illuminate\Support\Collection
    {
        $referredByUserId = $originalTransaction->referred_by_user_id;

        if ($referredByUserId === null) {
            return collect();
        }

        $txDate = $originalTransaction->transaction_date;

        $runIds = CommissionRun::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $originalTransaction->company_id)
            ->where('status', 'completed')
            ->whereDate('run_date', '>=', $txDate->toDateString())
            ->whereDate('run_date', '<=', $txDate->copy()->addDays(29)->toDateString())
            ->pluck('id');

        if ($runIds->isEmpty()) {
            return collect();
        }

        return BonusLedgerEntry::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $originalTransaction->company_id)
            ->where('user_id', $referredByUserId)
            ->whereIn('commission_run_id', $runIds)
            ->where('amount', '>', 0)
            ->get();
    }

    /**
     * Create a clawback wallet movement for a single ledger entry.
     *
     * @param CommissionLedgerEntry|BonusLedgerEntry $entry
     * @param string $referenceType 'commission_ledger_entry' or 'bonus_ledger_entry'
     * @param Transaction $refundTransaction The refund that triggered this clawback
     * @param string $ratio Proportional ratio (bcmath string, 0-1)
     * @return array|null Clawback detail, or null if skipped (no wallet account)
     */
    private function createClawbackMovement(
        CommissionLedgerEntry|BonusLedgerEntry $entry,
        string $referenceType,
        Transaction $refundTransaction,
        string $ratio,
    ): ?array {
        // Calculate proportional clawback amount
        $clawbackAmount = bcmul((string) $entry->amount, $ratio, 4);

        // Skip zero clawbacks
        if (bccomp($clawbackAmount, '0', 4) <= 0) {
            return null;
        }

        // Find the affiliate's wallet account
        $walletAccount = WalletAccount::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $refundTransaction->company_id)
            ->where('user_id', $entry->user_id)
            ->first();

        if ($walletAccount === null) {
            Log::warning(sprintf(
                'Clawback skipped for user #%d — no wallet account found (refund #%d, %s #%d)',
                $entry->user_id,
                $refundTransaction->id,
                $referenceType,
                $entry->id,
            ));

            return null;
        }

        // Create negative wallet movement (clawback)
        $negativeAmount = bcmul($clawbackAmount, '-1', 4);

        $movement = WalletMovement::create([
            'company_id' => $refundTransaction->company_id,
            'wallet_account_id' => $walletAccount->id,
            'type' => 'clawback',
            'amount' => $negativeAmount,
            'status' => 'approved',
            'reference_type' => $referenceType,
            'reference_id' => $entry->id,
            'description' => sprintf(
                'Clawback for refund #%d: %s of %s entry #%d',
                $refundTransaction->id,
                bccomp($ratio, '1', 10) === 0 ? 'full' : 'partial',
                $referenceType === 'commission_ledger_entry' ? 'commission' : 'bonus',
                $entry->id,
            ),
            'effective_at' => now(),
            'created_at' => now(),
        ]);

        return [
            'ledger_type' => $referenceType,
            'ledger_entry_id' => $entry->id,
            'wallet_movement_id' => $movement->id,
            'original_amount' => (string) $entry->amount,
            'clawback_amount' => $clawbackAmount,
            'ratio' => $ratio,
        ];
    }
}
