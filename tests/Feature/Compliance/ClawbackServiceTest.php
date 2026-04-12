<?php

namespace Tests\Feature\Compliance;

use App\Exceptions\Commission\ClawbackException;
use App\Models\CommissionLedgerEntry;
use App\Models\CommissionRun;
use App\Models\Transaction;
use App\Models\WalletMovement;
use App\Scopes\CompanyScope;
use App\Services\Commission\ClawbackService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Commission\CommissionTestCase;

class ClawbackServiceTest extends CommissionTestCase
{
    private ClawbackService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ClawbackService();
    }

    /** @test */
    public function full_refund_claws_back_full_commission(): void
    {
        $this->createCompanyWithPlan();

        $affiliate = $this->createAffiliate('Earner');
        $customer  = $this->createCustomer('Buyer', $this->nodeFor($affiliate));

        // Original purchase: $500 / 50 XP, referred by affiliate
        $original = $this->createTransaction($customer, 50, $affiliate, 5, 'purchase', 'confirmed', true);

        // Commission run and ledger entry: affiliate earned $50 from this transaction
        $run   = $this->createCompletedCommissionRun(5);
        $entry = $this->createCommissionLedgerEntry($run, $affiliate->id, '50.0000');

        // Full refund: same XP as original
        $refund = Transaction::withoutGlobalScopes()->create([
            'company_id'             => $this->company->id,
            'user_id'                => $customer->id,
            'referred_by_user_id'    => $affiliate->id,
            'type'                   => 'refund',
            'amount'                 => '500.00',
            'xp'                     => '50',
            'currency'               => 'USD',
            'status'                 => 'confirmed',
            'qualifies_for_commission' => false,
            'transaction_date'       => $this->today->toDateString(),
            'reference'              => (string) $original->id,
        ]);

        $result = $this->service->processRefund($refund);

        // Full refund → ratio = 1.0 → clawback = $50
        $this->assertSame(0, bccomp('50.0000', $result->total_clawback_amount, 4),
            "Expected full clawback 50.0000, got {$result->total_clawback_amount}");
        $this->assertFalse($result->is_partial);

        // Verify wallet movement was created with type='clawback' and negative amount
        $movement = WalletMovement::withoutGlobalScope(CompanyScope::class)
            ->where('type', 'clawback')
            ->where('reference_type', 'commission_ledger_entry')
            ->where('reference_id', $entry->id)
            ->first();

        $this->assertNotNull($movement, 'Expected a clawback wallet movement to exist');
        $this->assertSame(0, bccomp('-50.0000', (string) $movement->amount, 4),
            "Expected movement amount -50.0000, got {$movement->amount}");
    }

    /** @test */
    public function partial_refund_claws_back_proportionally(): void
    {
        $this->createCompanyWithPlan();

        $affiliate = $this->createAffiliate('PartialEarner');
        $customer  = $this->createCustomer('PartialBuyer', $this->nodeFor($affiliate));

        // Original: 50 XP → referred by affiliate
        $original = $this->createTransaction($customer, 50, $affiliate, 5, 'purchase', 'confirmed', true);

        // Affiliate earned $50 commission
        $run   = $this->createCompletedCommissionRun(5);
        $entry = $this->createCommissionLedgerEntry($run, $affiliate->id, '50.0000');

        // Partial refund: 20 XP out of 50 XP → ratio = 20/50 = 0.40
        $refund = Transaction::withoutGlobalScopes()->create([
            'company_id'             => $this->company->id,
            'user_id'                => $customer->id,
            'referred_by_user_id'    => $affiliate->id,
            'type'                   => 'refund',
            'amount'                 => '200.00',
            'xp'                     => '20',
            'currency'               => 'USD',
            'status'                 => 'confirmed',
            'qualifies_for_commission' => false,
            'transaction_date'       => $this->today->toDateString(),
            'reference'              => (string) $original->id,
        ]);

        $result = $this->service->processRefund($refund);

        // 40% of $50 = $20
        $this->assertSame(0, bccomp('20.0000', $result->total_clawback_amount, 4),
            "Expected partial clawback 20.0000, got {$result->total_clawback_amount}");
        $this->assertTrue($result->is_partial);

        $movement = WalletMovement::withoutGlobalScope(CompanyScope::class)
            ->where('type', 'clawback')
            ->where('reference_type', 'commission_ledger_entry')
            ->where('reference_id', $entry->id)
            ->first();

        $this->assertNotNull($movement);
        $this->assertSame(0, bccomp('-20.0000', (string) $movement->amount, 4),
            "Expected movement amount -20.0000, got {$movement->amount}");
    }

    /** @test */
    public function no_clawback_for_non_commission_transaction(): void
    {
        $this->createCompanyWithPlan();

        $affiliate = $this->createAffiliate('NoCommEarner');
        $customer  = $this->createCustomer('NoCommBuyer', $this->nodeFor($affiliate));

        // Original purchase has low XP (below 20 XP threshold) → no commissions were earned
        $original = $this->createTransaction($customer, 15, $affiliate, 5, 'purchase', 'confirmed', false);
        // No commission ledger entry created (no run, no entry)

        $refund = Transaction::withoutGlobalScopes()->create([
            'company_id'             => $this->company->id,
            'user_id'                => $customer->id,
            'referred_by_user_id'    => $affiliate->id,
            'type'                   => 'refund',
            'amount'                 => '150.00',
            'xp'                     => '15',
            'currency'               => 'USD',
            'status'                 => 'confirmed',
            'qualifies_for_commission' => false,
            'transaction_date'       => $this->today->toDateString(),
            'reference'              => (string) $original->id,
        ]);

        $result = $this->service->processRefund($refund);

        // No commissions found → clawback amount = 0
        $this->assertSame(0, bccomp('0.0000', $result->total_clawback_amount, 4),
            "Expected no clawback for non-commissioned transaction");
        $this->assertEmpty($result->clawback_details);
    }

    /** @test */
    public function idempotent_refund_processing(): void
    {
        $this->createCompanyWithPlan();

        $affiliate = $this->createAffiliate('IdempotentEarner');
        $customer  = $this->createCustomer('IdempotentBuyer', $this->nodeFor($affiliate));

        $original = $this->createTransaction($customer, 50, $affiliate, 5, 'purchase', 'confirmed', true);

        $run   = $this->createCompletedCommissionRun(5);
        $entry = $this->createCommissionLedgerEntry($run, $affiliate->id, '50.0000');

        $refund = Transaction::withoutGlobalScopes()->create([
            'company_id'             => $this->company->id,
            'user_id'                => $customer->id,
            'referred_by_user_id'    => $affiliate->id,
            'type'                   => 'refund',
            'amount'                 => '500.00',
            'xp'                     => '50',
            'currency'               => 'USD',
            'status'                 => 'confirmed',
            'qualifies_for_commission' => false,
            'transaction_date'       => $this->today->toDateString(),
            'reference'              => (string) $original->id,
        ]);

        // Process once
        $first = $this->service->processRefund($refund);
        $this->assertSame(0, bccomp('50.0000', $first->total_clawback_amount, 4));

        // Process again — should be a no-op (idempotency)
        $second = $this->service->processRefund($refund);
        $this->assertSame(0, bccomp('0.0000', $second->total_clawback_amount, 4),
            'Second call should return 0 (already processed)');

        // Only one real clawback movement (plus one sentinel) should exist
        $clawbackMovements = WalletMovement::withoutGlobalScope(CompanyScope::class)
            ->where('type', 'clawback')
            ->where('reference_type', 'commission_ledger_entry')
            ->where('reference_id', $entry->id)
            ->count();

        $this->assertSame(1, $clawbackMovements,
            'Clawback movement for ledger entry must exist exactly once');
    }

    /** @test */
    public function clawback_creates_negative_wallet_movement(): void
    {
        $this->createCompanyWithPlan();

        $affiliate = $this->createAffiliate('NegMover');
        $customer  = $this->createCustomer('NegBuyer', $this->nodeFor($affiliate));

        $original = $this->createTransaction($customer, 50, $affiliate, 5, 'purchase', 'confirmed', true);
        $run      = $this->createCompletedCommissionRun(5);
        $entry    = $this->createCommissionLedgerEntry($run, $affiliate->id, '75.0000');

        $refund = Transaction::withoutGlobalScopes()->create([
            'company_id'             => $this->company->id,
            'user_id'                => $customer->id,
            'referred_by_user_id'    => $affiliate->id,
            'type'                   => 'refund',
            'amount'                 => '500.00',
            'xp'                     => '50',
            'currency'               => 'USD',
            'status'                 => 'confirmed',
            'qualifies_for_commission' => false,
            'transaction_date'       => $this->today->toDateString(),
            'reference'              => (string) $original->id,
        ]);

        $this->service->processRefund($refund);

        $movement = WalletMovement::withoutGlobalScope(CompanyScope::class)
            ->where('type', 'clawback')
            ->where('reference_type', 'commission_ledger_entry')
            ->where('reference_id', $entry->id)
            ->first();

        $this->assertNotNull($movement, 'Clawback wallet movement must be created');
        $this->assertSame('clawback', $movement->type);
        $this->assertSame('approved', $movement->status);

        // Amount must be negative
        $this->assertSame(
            -1,
            bccomp((string) $movement->amount, '0', 4),
            "Clawback wallet movement amount must be negative, got {$movement->amount}"
        );
    }

    /** @test */
    public function missing_original_transaction_throws(): void
    {
        $this->createCompanyWithPlan();

        $affiliate = $this->createAffiliate('ThrowsAffiliate');
        $customer  = $this->createCustomer('ThrowsBuyer', $this->nodeFor($affiliate));

        // Refund that references a non-existent original transaction ID
        $refund = Transaction::withoutGlobalScopes()->create([
            'company_id'             => $this->company->id,
            'user_id'                => $customer->id,
            'referred_by_user_id'    => $affiliate->id,
            'type'                   => 'refund',
            'amount'                 => '100.00',
            'xp'                     => '20',
            'currency'               => 'USD',
            'status'                 => 'confirmed',
            'qualifies_for_commission' => false,
            'transaction_date'       => $this->today->toDateString(),
            'reference'              => '999999',  // non-existent ID
        ]);

        $this->expectException(ClawbackException::class);
        $this->service->processRefund($refund);
    }

    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    private function createCompletedCommissionRun(int $daysAgo = 0): CommissionRun
    {
        return CommissionRun::create([
            'company_id'                  => $this->company->id,
            'compensation_plan_id'        => $this->plan->id,
            'run_date'                    => $this->today->copy()->subDays($daysAgo)->toDateString(),
            'status'                      => 'completed',
            'total_affiliate_commission'  => '0',
            'total_viral_commission'      => '0',
            'total_bonus_amount'          => '0',
            'total_company_volume'        => '0',
            'viral_cap_triggered'         => false,
            'started_at'                  => now(),
            'completed_at'                => now(),
        ]);
    }

    private function createCommissionLedgerEntry(CommissionRun $run, int $userId, string $amount): CommissionLedgerEntry
    {
        return CommissionLedgerEntry::create([
            'company_id'        => $this->company->id,
            'commission_run_id' => $run->id,
            'user_id'           => $userId,
            'type'              => 'affiliate_commission',
            'amount'            => $amount,
            'tier_achieved'     => 1,
            'created_at'        => now(),
        ]);
    }
}
