<?php

namespace Tests\Feature\Commission;

use App\Models\BonusLedgerEntry;
use App\Models\CommissionRun;
use App\Models\User;
use App\Scopes\CompanyScope;
use App\Services\Commission\Bonus\BonusOrchestrator;
use Illuminate\Support\Facades\DB;

/**
 * Integration tests for BonusOrchestrator.
 *
 * BonusOrchestrator coordinates all active bonus types for a commission run.
 * It:
 *   1. Loads bonus_types ordered by priority ASC for the company + plan.
 *   2. Skips inactive types.
 *   3. Dispatches each active type to its calculator.
 *   4. Writes BonusLedgerEntry rows for every non-zero result.
 *   5. Sums all bonus amounts and stores total_bonus_amount on CommissionRun.
 *   6. Applies the global 35% cap to (commission + bonuses) combined.
 *
 * Service signature (expected by calc-engine):
 *   class BonusOrchestrator {
 *       public function run(
 *           CommissionRun $run,
 *           Collection $affiliates,
 *           Collection $commissionResults,
 *           Carbon $date,
 *       ): BonusOrchestratorResult;
 *   }
 *
 * BonusOrchestratorResult (plain object / DTO):
 *   - total_bonus_amount: string (bcmath string)
 *   - entries: Collection<BonusLedgerEntry>
 *   - cap_triggered: bool
 *
 * These tests are TDD-first. BonusOrchestrator does not yet exist.
 */
class BonusOrchestratorIntegrationTest extends BonusTestCase
{
    private BonusOrchestrator $orchestrator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createCompanyWithPlan();
        $this->orchestrator = app(BonusOrchestrator::class);
    }

    /**
     * @test
     *
     * When there are no bonus_type rows for the company+plan, the orchestrator
     * must complete gracefully with total_bonus_amount = '0' and an empty
     * entries collection. It must NOT throw.
     */
    public function no_bonus_types_total_bonus_zero(): void
    {
        $affiliate = $this->createAffiliate('Affiliate A');
        $run       = $this->createCommissionRun(['status' => 'running']);

        $affiliates = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->company->id)
            ->where('role', 'affiliate')
            ->get();

        $commissionResults = collect([
            $this->makeCommissionResult([
                'user_id'              => $affiliate->id,
                'affiliate_commission' => '100.0000',
                'viral_commission'     => '12.5000',
            ]),
        ]);

        $result = $this->orchestrator->run($run, $affiliates, $commissionResults, $this->today);

        $this->assertBonusEquals('0', (string) $result->total_bonus_amount);
        $this->assertTrue($result->entries->isEmpty(), 'No bonus types → no ledger entries');
        $this->assertFalse($result->cap_triggered, 'Cap should not trigger with $0 bonus');

        // Verify no rows written to bonus_ledger_entries
        $count = BonusLedgerEntry::withoutGlobalScope(CompanyScope::class)
            ->where('commission_run_id', $run->id)
            ->count();
        $this->assertEquals(0, $count);
    }

    /**
     * @test
     *
     * Two active bonus types (matching + rank advancement) both calculate and
     * produce ledger entries. The total_bonus_amount on the result must equal
     * the sum of all entries. CommissionRun.total_bonus_amount must be updated.
     */
    public function two_active_bonus_types_both_calculate(): void
    {
        $affiliateA = $this->createAffiliate('Affiliate A');
        $nodeA      = $this->nodeFor($affiliateA);
        $affiliateB = $this->createAffiliate('Affiliate B', $nodeA);

        $run = $this->createCommissionRun(['status' => 'running']);

        // Matching bonus: gen1 = 15%
        $matchingBonus = $this->createBonusType([
            'type'      => 'matching',
            'name'      => 'Matching',
            'is_active' => true,
            'priority'  => 1,
        ]);
        $this->createBonusTier($matchingBonus, [
            'level' => 1,
            'rate'  => '0.1500',
            'qualifier_type'  => 'generation',
            'qualifier_value' => 1,
        ]);

        // Rank bonus: Gold = $500
        $rankBonus = $this->createBonusType([
            'type'      => 'rank_advancement',
            'name'      => 'Rank Advancement',
            'is_active' => true,
            'priority'  => 2,
        ]);
        $this->createBonusTier($rankBonus, [
            'level'          => 3,
            'label'          => 'Gold',
            'qualifier_type' => 'rank',
            'qualifier_value'=> 3,
            'amount'         => '500.0000',
        ]);

        $affiliates = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->company->id)
            ->where('role', 'affiliate')
            ->get();

        // B earns $1,000 and achieves Gold rank
        $commissionResults = collect([
            $this->makeCommissionResult([
                'user_id'              => $affiliateB->id,
                'affiliate_commission' => '1000.0000',
                'viral_commission'     => '0',
                'qualification_snapshot' => [
                    'current_rank' => 3,
                ],
            ]),
            $this->makeCommissionResult([
                'user_id'              => $affiliateA->id,
                'affiliate_commission' => '0',
                'viral_commission'     => '0',
                'qualification_snapshot' => [
                    'current_rank' => null,
                ],
            ]),
        ]);

        $result = $this->orchestrator->run($run, $affiliates, $commissionResults, $this->today);

        // At minimum: A gets $150 matching (15% of B's $1000) + B gets $500 rank = $650
        $this->assertTrue(
            bccomp((string) $result->total_bonus_amount, '0', 4) > 0,
            'Two active bonus types should produce non-zero total bonus'
        );

        // Verify entries exist in the database
        $dbCount = BonusLedgerEntry::withoutGlobalScope(CompanyScope::class)
            ->where('commission_run_id', $run->id)
            ->count();
        $this->assertGreaterThan(0, $dbCount, 'Bonus ledger entries must be written to DB');

        // Verify run record is updated
        $freshRun = CommissionRun::withoutGlobalScope(CompanyScope::class)->find($run->id);
        $this->assertBonusEquals(
            (string) $result->total_bonus_amount,
            (string) $freshRun->total_bonus_amount,
            'total_bonus_amount on CommissionRun must reflect sum of all bonus entries'
        );
    }

    /**
     * @test
     *
     * Priority ordering: bonus type with priority=1 must execute before priority=2.
     * We verify this by examining the created_at ordering of bonus_ledger_entries
     * (lower priority runs first, so its entries appear first in insertion order).
     *
     * Two matching bonus types at different priorities; both pay different amounts.
     * The test confirms entries are inserted in priority order.
     */
    public function priority_ordering(): void
    {
        $affiliateA = $this->createAffiliate('Affiliate A');
        $nodeA      = $this->nodeFor($affiliateA);
        $affiliateB = $this->createAffiliate('Affiliate B', $nodeA);

        $run = $this->createCommissionRun(['status' => 'running']);

        // Priority 1 (runs first): gen1 = 10%
        $bonus1 = $this->createBonusType([
            'type'      => 'matching',
            'name'      => 'Matching P1',
            'is_active' => true,
            'priority'  => 1,
        ]);
        $this->createBonusTier($bonus1, [
            'level' => 1,
            'rate'  => '0.1000',
            'qualifier_type'  => 'generation',
            'qualifier_value' => 1,
        ]);

        // Priority 2 (runs second): gen1 = 5%
        $bonus2 = $this->createBonusType([
            'type'      => 'matching',
            'name'      => 'Matching P2',
            'is_active' => true,
            'priority'  => 2,
        ]);
        $this->createBonusTier($bonus2, [
            'level' => 1,
            'rate'  => '0.0500',
            'qualifier_type'  => 'generation',
            'qualifier_value' => 1,
        ]);

        $affiliates = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->company->id)
            ->where('role', 'affiliate')
            ->get();

        $commissionResults = collect([
            $this->makeCommissionResult([
                'user_id'              => $affiliateB->id,
                'affiliate_commission' => '1000.0000',
            ]),
        ]);

        $result = $this->orchestrator->run($run, $affiliates, $commissionResults, $this->today);

        // Retrieve entries ordered by auto-increment id (insertion order)
        $entries = BonusLedgerEntry::withoutGlobalScope(CompanyScope::class)
            ->where('commission_run_id', $run->id)
            ->where('user_id', $affiliateA->id)
            ->orderBy('id')
            ->get();

        $this->assertGreaterThanOrEqual(2, $entries->count(),
            'A should have at least 2 entries (one per bonus type)');

        // First entry should come from priority=1 bonus (10% → $100)
        $this->assertBonusEquals('100.0000', (string) $entries->first()->amount,
            'Priority 1 bonus (10%) should produce first entry');

        // Second entry should come from priority=2 bonus (5% → $50)
        $this->assertBonusEquals('50.0000', (string) $entries->skip(1)->first()->amount,
            'Priority 2 bonus (5%) should produce second entry');
    }

    /**
     * @test
     *
     * Mixed active/inactive: 3 bonus types, 1 is inactive.
     * Only 2 active types execute; the inactive one produces no entries.
     */
    public function mixed_active_inactive(): void
    {
        $affiliateA = $this->createAffiliate('Affiliate A');
        $nodeA      = $this->nodeFor($affiliateA);
        $affiliateB = $this->createAffiliate('Affiliate B', $nodeA);

        $run = $this->createCommissionRun(['status' => 'running']);

        // Active type 1
        $active1 = $this->createBonusType([
            'type'      => 'matching',
            'name'      => 'Active Matching 1',
            'is_active' => true,
            'priority'  => 1,
        ]);
        $this->createBonusTier($active1, [
            'level' => 1,
            'rate'  => '0.1000',
            'qualifier_type'  => 'generation',
            'qualifier_value' => 1,
        ]);

        // INACTIVE type — must be skipped
        $inactive = $this->createBonusType([
            'type'      => 'rank_advancement',
            'name'      => 'Inactive Rank Bonus',
            'is_active' => false,
            'priority'  => 2,
        ]);
        $this->createBonusTier($inactive, [
            'level'          => 1,
            'label'          => 'Bronze',
            'qualifier_type' => 'rank',
            'qualifier_value'=> 1,
            'amount'         => '999.0000', // large to detect if incorrectly included
        ]);

        // Active type 2
        $active2 = $this->createBonusType([
            'type'      => 'matching',
            'name'      => 'Active Matching 2',
            'is_active' => true,
            'priority'  => 3,
        ]);
        $this->createBonusTier($active2, [
            'level' => 1,
            'rate'  => '0.0500',
            'qualifier_type'  => 'generation',
            'qualifier_value' => 1,
        ]);

        $affiliates = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->company->id)
            ->where('role', 'affiliate')
            ->get();

        $commissionResults = collect([
            $this->makeCommissionResult([
                'user_id'              => $affiliateB->id,
                'affiliate_commission' => '1000.0000',
                'qualification_snapshot' => ['current_rank' => 1],
            ]),
        ]);

        $result = $this->orchestrator->run($run, $affiliates, $commissionResults, $this->today);

        // Active 1: A gets 1000*0.10 = $100
        // Active 2: A gets 1000*0.05 = $50
        // Inactive: $0 (skipped)
        // Total: $150
        $this->assertBonusEquals('150.0000', (string) $result->total_bonus_amount,
            'Only 2 active types should contribute; inactive type must be skipped');

        // Verify the inactive bonus type has NO ledger entries
        $inactiveEntries = BonusLedgerEntry::withoutGlobalScope(CompanyScope::class)
            ->where('commission_run_id', $run->id)
            ->where('bonus_type_id', $inactive->id)
            ->count();
        $this->assertEquals(0, $inactiveEntries, 'Inactive bonus type must produce zero ledger entries');
    }

    /**
     * @test
     *
     * Global 35% cap check: bonuses are included in the total payout cap.
     *
     * Setup: commission + bonuses would exceed 35% of rolling company volume.
     * The orchestrator must apply the global cap proportionally to bonus amounts.
     *
     * Company rolling 30d volume: $1,000.
     * 35% cap = $350.
     * Commissions already paid (rolling): $340 (34%).
     * Bonus to be paid today: $50.
     * Total rolling after bonus: $390 → 39% → over cap.
     * Overage: 4%. Reduction multiplier = (1 - 0.04) = 0.96.
     * Adjusted bonus: $50 * 0.96 = $48.
     */
    public function bonuses_included_in_global_cap(): void
    {
        $affiliate = $this->createAffiliate('Capped Affiliate');
        $run       = $this->createCommissionRun(['status' => 'running']);

        // Seed rolling 30-day company volume via transactions
        $customer = $this->createCustomer('Volume Customer');
        for ($i = 1; $i <= 10; $i++) {
            $this->createTransaction($customer, 100, daysAgo: $i); // 10 * 100 = 1000
        }

        // Seed rolling 30-day commission already paid: $340
        $priorRun = $this->createCommissionRun([
            'run_date' => $this->today->copy()->subDays(5)->toDateString(),
            'status'   => 'completed',
            'total_affiliate_commission' => '340',
            'total_viral_commission'     => '0',
        ]);

        \App\Models\CommissionLedgerEntry::create([
            'company_id'             => $this->company->id,
            'commission_run_id'      => $priorRun->id,
            'user_id'                => $affiliate->id,
            'type'                   => 'affiliate_commission',
            'amount'                 => '340.0000',
            'qualification_snapshot' => [],
            'created_at'             => now()->subDays(5),
        ]);

        // Bonus type that would pay $50 today
        $bonusType = $this->createBonusType([
            'type'      => 'matching',
            'name'      => 'Cap Test Matching',
            'is_active' => true,
            'priority'  => 1,
        ]);
        $this->createBonusTier($bonusType, [
            'level' => 1,
            'rate'  => '0.0500',
            'qualifier_type'  => 'generation',
            'qualifier_value' => 1,
        ]);

        $nodeA = $this->nodeFor($affiliate);
        $affiliateB = $this->createAffiliate('Downline', $nodeA);

        $affiliates = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->company->id)
            ->where('role', 'affiliate')
            ->get();

        // B earns $1,000 commission → A would receive $50 matching
        $commissionResults = collect([
            $this->makeCommissionResult([
                'user_id'              => $affiliateB->id,
                'affiliate_commission' => '1000.0000',
            ]),
            $this->makeCommissionResult([
                'user_id'              => $affiliate->id,
                'affiliate_commission' => '0',
            ]),
        ]);

        $result = $this->orchestrator->run($run, $affiliates, $commissionResults, $this->today);

        // Cap is triggered because rolling commissions ($340) + today's bonus ($50) = $390 > $350
        $this->assertTrue($result->cap_triggered,
            'Global cap should be triggered when commission + bonus exceeds 35% of volume');

        // Adjusted bonus must be strictly less than $50
        $this->assertTrue(
            bccomp((string) $result->total_bonus_amount, '50.0000', 4) < 0,
            'Bonus should be reduced when global cap is triggered'
        );

        // Adjusted bonus must be greater than $0
        $this->assertTrue(
            bccomp((string) $result->total_bonus_amount, '0', 4) > 0,
            'Adjusted bonus should remain positive (partial, not zeroed out)'
        );
    }
}
