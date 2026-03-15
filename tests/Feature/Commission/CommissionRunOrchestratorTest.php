<?php

namespace Tests\Feature\Commission;

use App\Models\CommissionLedgerEntry;
use App\Models\CommissionRun;
use App\Models\WalletMovement;
use App\Services\Commission\CommissionRunOrchestrator;

class CommissionRunOrchestratorTest extends CommissionTestCase
{
    private CommissionRunOrchestrator $orchestrator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orchestrator = app(CommissionRunOrchestrator::class);
        $this->createCompanyWithPlan();
    }

    /** @test — Section 8 Test 9: Idempotent Commission Run */
    public function idempotent_commission_run(): void
    {
        $affiliate = $this->createAffiliate('Affiliate A');
        $node = $this->nodeFor($affiliate);
        $c1 = $this->createCustomer('C1', $node);
        $this->createTransaction($c1, 50, referredBy: $affiliate, daysAgo: 0);

        // First run
        $run1 = $this->orchestrator->run($this->company, $this->today);
        $run1Total = bcadd($run1->total_affiliate_commission, $run1->total_viral_commission, 4);
        $run1Entries = $run1->ledgerEntries->count();

        // Second run — should produce identical results
        $run2 = $this->orchestrator->run($this->company, $this->today);
        $run2Total = bcadd($run2->total_affiliate_commission, $run2->total_viral_commission, 4);
        $run2Entries = $run2->ledgerEntries->count();

        $this->assertEquals('completed', $run2->status);
        $this->assertCommissionEquals($run1Total, $run2Total);
        $this->assertEquals($run1Entries, $run2Entries);

        // Only 1 run record should exist
        $this->assertEquals(1, CommissionRun::withoutGlobalScopes()
            ->where('company_id', $this->company->id)
            ->count());
    }

    /** @test — Commission run with no transactions produces zero */
    public function commission_run_with_no_transactions(): void
    {
        $affiliate = $this->createAffiliate('Affiliate A');

        $run = $this->orchestrator->run($this->company, $this->today);

        $this->assertEquals('completed', $run->status);
        $this->assertCommissionEquals('0', $run->total_affiliate_commission);
        $this->assertCommissionEquals('0', $run->total_viral_commission);
        $this->assertEquals(0, $run->ledgerEntries->count());
    }

    /** @test — Full affiliate + viral flow through orchestrator */
    public function full_commission_run_with_affiliate_and_viral(): void
    {
        // Affiliate with 3 legs, each having customers
        $affiliate = $this->createAffiliate('Main Affiliate');
        $mainNode = $this->nodeFor($affiliate);

        // Leg 1
        $leg1Root = $this->createAffiliate('Leg1', $mainNode);
        $leg1Node = $this->nodeFor($leg1Root);
        $leg1c = $this->createCustomer('L1C', $leg1Node);

        // Leg 2
        $leg2Root = $this->createAffiliate('Leg2', $mainNode);
        $leg2Node = $this->nodeFor($leg2Root);

        // Direct referred customers (for affiliate qualification)
        $dc1 = $this->createCustomer('DC1', $mainNode);
        $dc2 = $this->createCustomer('DC2', $mainNode);

        // Transactions for direct referrals (affiliate tier qualification)
        $this->createTransaction($dc1, 50, referredBy: $affiliate, daysAgo: 5);
        $this->createTransaction($dc2, 50, referredBy: $affiliate, daysAgo: 3);
        $this->createTransaction($dc1, 30, referredBy: $affiliate, daysAgo: 0); // Today

        // Leg volume (for viral)
        $this->createTransaction($leg1Root, 200, daysAgo: 5);
        $this->createTransaction($leg1c, 100, daysAgo: 3);
        $this->createTransaction($leg2Root, 150, daysAgo: 7);

        $run = $this->orchestrator->run($this->company, $this->today);

        $this->assertEquals('completed', $run->status);

        // Main affiliate should have at least some commission
        $entries = CommissionLedgerEntry::withoutGlobalScopes()
            ->where('commission_run_id', $run->id)
            ->where('user_id', $affiliate->id)
            ->get();

        // Should have affiliate commission (2 customers, volume >= 0)
        $affiliateEntry = $entries->firstWhere('type', 'affiliate_commission');
        $this->assertNotNull($affiliateEntry);
        $this->assertTrue(bccomp($affiliateEntry->amount, '0', 4) > 0);
    }

    /** @test — Idempotent re-run cleans up wallet movements */
    public function idempotent_rerun_cleans_wallet_movements(): void
    {
        $affiliate = $this->createAffiliate('Affiliate A');
        $node = $this->nodeFor($affiliate);
        $c1 = $this->createCustomer('C1', $node);
        $this->createTransaction($c1, 50, referredBy: $affiliate, daysAgo: 0);

        // Run commissions + credit wallet
        $this->orchestrator->run($this->company, $this->today);

        $walletService = app(\App\Services\Commission\WalletCreditService::class);
        $walletService->creditFromCommissions($this->company, $this->today);

        $movementsBefore = WalletMovement::withoutGlobalScopes()->count();

        // Re-run commissions (should clean up old wallet movements)
        $this->orchestrator->run($this->company, $this->today);

        // Old wallet movements should be deleted
        $movementsAfter = WalletMovement::withoutGlobalScopes()->count();
        $this->assertEquals(0, $movementsAfter);
    }

    /** @test — Edge case: plan version transition */
    public function plan_version_transition(): void
    {
        // Create a new plan that takes effect today with different rates
        $this->plan->update(['effective_until' => $this->today->copy()->subDay()]);

        $newPlan = \App\Models\CompensationPlan::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Plan v2',
            'version' => '2.0',
            'config' => array_replace_recursive($this->soCommConfig(), [
                'affiliate_commission' => [
                    'tiers' => [
                        ['min_active_customers' => 1, 'min_referred_volume' => 0, 'rate' => 0.20], // 20% instead of 10%
                    ],
                ],
            ]),
            'effective_from' => $this->today->toDateString(),
            'is_active' => true,
        ]);

        $affiliate = $this->createAffiliate('Affiliate A');
        $c1 = $this->createCustomer('C1', $this->nodeFor($affiliate));
        $this->createTransaction($c1, 100, referredBy: $affiliate, daysAgo: 0);

        $run = $this->orchestrator->run($this->company, $this->today);

        // Should use the new plan's 20% rate
        $this->assertEquals($newPlan->id, $run->compensation_plan_id);
        $entry = $run->ledgerEntries->firstWhere('type', 'affiliate_commission');
        $this->assertNotNull($entry);
        $this->assertCommissionEquals('20.0000', $entry->amount); // 100 * 0.20
    }
}
