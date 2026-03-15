<?php

namespace Tests\Feature\Commission;

use App\Models\WalletMovement;
use App\Services\Commission\CommissionRunOrchestrator;
use App\Services\Commission\WalletCreditService;

class WalletCreditServiceTest extends CommissionTestCase
{
    private WalletCreditService $walletService;
    private CommissionRunOrchestrator $orchestrator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->walletService = new WalletCreditService();
        $this->orchestrator = app(CommissionRunOrchestrator::class);
        $this->createCompanyWithPlan();
    }

    /** @test — Section 8 Test 10: Wallet Double-Entry */
    public function wallet_movement_ledger(): void
    {
        // Create an affiliate that will earn both affiliate and viral commissions
        $affiliate = $this->createAffiliate('Affiliate A');
        $mainNode = $this->nodeFor($affiliate);

        // Need 2 active customers for viral tier + enough volume
        $c1 = $this->createCustomer('C1', $mainNode);
        $c2 = $this->createCustomer('C2', $mainNode);

        // Build up rolling volume and today volume for predictable affiliate commission
        // We want $100 affiliate: need today volume such that volume * rate = 100
        // With 2 customers and ~200+ volume, tier = 11% → need 909.09 XP today for $100
        // Simpler: use tier 1 (10%), need 1000 XP today → $100

        // Historical qualifying transactions
        $this->createTransaction($c1, 50, referredBy: $affiliate, daysAgo: 10);
        $this->createTransaction($c2, 50, referredBy: $affiliate, daysAgo: 5);

        // Today's volume: 1000 XP → at 10% = $100
        $this->createTransaction($c1, 1000, referredBy: $affiliate, daysAgo: 0);

        // Create legs for viral commission
        $leg1 = $this->createAffiliate('Leg1', $mainNode);
        $leg2 = $this->createAffiliate('Leg2', $mainNode);
        $this->createTransaction($leg1, 2000, daysAgo: 5); // Leg 1 volume
        $this->createTransaction($leg2, 1500, daysAgo: 3); // Leg 2 volume

        // Run commissions
        $run = $this->orchestrator->run($this->company, $this->today);

        // Credit wallets
        $credits = $this->walletService->creditFromCommissions($this->company, $this->today);

        // Verify wallet movements exist
        $this->assertGreaterThan(0, count($credits));

        // Verify wallet balance is derived from SUM
        $wallet = $affiliate->walletAccount;
        $expectedBalance = WalletMovement::withoutGlobalScopes()
            ->where('wallet_account_id', $wallet->id)
            ->where('status', '!=', 'reversed')
            ->sum('amount');

        $this->assertCommissionEquals((string) $expectedBalance, $wallet->balance());

        // Verify no mutable balance field — wallet_accounts table has no balance column
        $this->assertFalse(
            \Schema::hasColumn('wallet_accounts', 'balance'),
            'wallet_accounts should not have a balance column'
        );
    }

    /** @test — Wallet credit idempotency */
    public function wallet_credit_is_idempotent(): void
    {
        $affiliate = $this->createAffiliate('Affiliate A');
        $c1 = $this->createCustomer('C1', $this->nodeFor($affiliate));
        $this->createTransaction($c1, 50, referredBy: $affiliate, daysAgo: 0);

        $this->orchestrator->run($this->company, $this->today);

        $credits1 = $this->walletService->creditFromCommissions($this->company, $this->today);
        $credits2 = $this->walletService->creditFromCommissions($this->company, $this->today);

        $this->assertGreaterThan(0, count($credits1));
        $this->assertCount(0, $credits2); // No new credits on second run
    }
}
