<?php

namespace Tests\Feature\Commission;

use App\Models\BonusLedgerEntry;
use App\Models\User;
use App\Scopes\CompanyScope;
use App\Services\Commission\Bonus\RankAdvancementBonusCalculator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Tests for RankAdvancementBonusCalculator.
 *
 * Rank Advancement Bonus pays a one-time lump sum when an affiliate achieves a
 * new rank for the first time. If the rank was already achieved previously (an
 * existing bonus_ledger_entry for that tier exists), no repeat payout is made.
 *
 * Tiers are stored in bonus_type_tiers with:
 *   level       = rank ordinal (e.g. 1=Bronze, 2=Silver, 3=Gold)
 *   label       = rank name
 *   amount      = one-time bonus amount
 *   qualifier_type  = 'rank'
 *   qualifier_value = rank numeric threshold (e.g. active_customer_count or volume)
 *
 * The qualification_snapshot on commission results must include 'current_rank'
 * to indicate which rank the affiliate has just achieved.
 *
 * These tests are TDD-first. RankAdvancementBonusCalculator does not yet exist.
 */
class RankAdvancementBonusCalculatorTest extends BonusTestCase
{
    private RankAdvancementBonusCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createCompanyWithPlan();
        $this->calculator = new RankAdvancementBonusCalculator();
    }

    /**
     * @test
     *
     * Affiliate hits Gold rank for the very first time (no prior rank_advancement
     * ledger entries for them). Expected: Gold one-time bonus of $500 is awarded.
     */
    public function new_rank_achieved_pays_one_time_bonus(): void
    {
        $affiliate = $this->createAffiliate('Gold Achiever');

        $bonusType = $this->createBonusType([
            'type'      => 'rank_advancement',
            'name'      => 'Rank Advancement Bonus',
            'is_active' => true,
        ]);
        $this->createBonusTier($bonusType, [
            'level'          => 1,
            'label'          => 'Bronze',
            'qualifier_type' => 'rank',
            'qualifier_value'=> 1,
            'amount'         => '100.0000',
        ]);
        $this->createBonusTier($bonusType, [
            'level'          => 2,
            'label'          => 'Silver',
            'qualifier_type' => 'rank',
            'qualifier_value'=> 2,
            'amount'         => '300.0000',
        ]);
        $this->createBonusTier($bonusType, [
            'level'          => 3,
            'label'          => 'Gold',
            'qualifier_type' => 'rank',
            'qualifier_value'=> 3,
            'amount'         => '500.0000',
        ]);

        // Commission result indicates affiliate just achieved rank 3 (Gold)
        $commissionResults = collect([
            $this->makeCommissionResult([
                'user_id' => $affiliate->id,
                'qualification_snapshot' => [
                    'current_rank' => 3,
                ],
            ]),
        ]);

        $affiliates = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->company->id)
            ->where('role', 'affiliate')
            ->get();

        $results = $this->calculator->calculate($bonusType, $affiliates, $commissionResults, $this->today);

        $entry = $results->firstWhere('user_id', $affiliate->id);
        $this->assertNotNull($entry, 'First-time Gold rank should pay the advancement bonus');
        $this->assertBonusEquals('500.0000', (string) $entry->amount);
        $this->assertEquals(3, $entry->tier_achieved);
    }

    /**
     * @test
     *
     * Affiliate previously achieved Gold rank (a prior bonus_ledger_entry exists
     * for bonus_type_id + user_id + tier_achieved = 3). No repeat payout.
     */
    public function already_achieved_rank_no_repeat_payout(): void
    {
        $affiliate = $this->createAffiliate('Already Gold Affiliate');

        $bonusType = $this->createBonusType([
            'type'      => 'rank_advancement',
            'is_active' => true,
        ]);
        $this->createBonusTier($bonusType, [
            'level'          => 3,
            'label'          => 'Gold',
            'qualifier_type' => 'rank',
            'qualifier_value'=> 3,
            'amount'         => '500.0000',
        ]);

        // Create an earlier commission run to hold the historical ledger entry
        $priorRun = $this->createCommissionRun([
            'run_date' => $this->today->copy()->subDays(30)->toDateString(),
            'status'   => 'completed',
        ]);

        // Simulate that affiliate already received Gold rank bonus
        DB::table('bonus_ledger_entries')->insert([
            'company_id'             => $this->company->id,
            'commission_run_id'      => $priorRun->id,
            'user_id'                => $affiliate->id,
            'bonus_type_id'          => $bonusType->id,
            'amount'                 => '500.0000',
            'tier_achieved'          => 3,
            'qualification_snapshot' => json_encode(['current_rank' => 3]),
            'description'            => 'Rank advancement: Gold',
            'created_at'             => now()->subDays(30),
        ]);

        $commissionResults = collect([
            $this->makeCommissionResult([
                'user_id' => $affiliate->id,
                'qualification_snapshot' => [
                    'current_rank' => 3,
                ],
            ]),
        ]);

        $affiliates = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->company->id)
            ->where('role', 'affiliate')
            ->get();

        $results = $this->calculator->calculate($bonusType, $affiliates, $commissionResults, $this->today);

        // Should be empty — rank already achieved, no repeat payout
        $entry = $results->firstWhere('user_id', $affiliate->id);
        if ($entry !== null) {
            $this->assertBonusEquals('0.0000', (string) $entry->amount,
                'Previously achieved rank must not pay again');
        } else {
            $this->assertTrue(true, 'No entry for already-achieved rank is correct');
        }
    }

    /**
     * @test
     *
     * Affiliate skipped Bronze and Silver, landing directly at Gold.
     * Only the Gold (highest achieved) bonus should be paid — not cumulative.
     * Bronze ($100) + Silver ($300) are NOT paid; only Gold ($500).
     */
    public function skipped_rank_pays_highest_achieved_only(): void
    {
        $affiliate = $this->createAffiliate('Rank Skipper');

        $bonusType = $this->createBonusType([
            'type'      => 'rank_advancement',
            'is_active' => true,
        ]);
        $this->createBonusTier($bonusType, [
            'level'          => 1,
            'label'          => 'Bronze',
            'qualifier_type' => 'rank',
            'qualifier_value'=> 1,
            'amount'         => '100.0000',
        ]);
        $this->createBonusTier($bonusType, [
            'level'          => 2,
            'label'          => 'Silver',
            'qualifier_type' => 'rank',
            'qualifier_value'=> 2,
            'amount'         => '300.0000',
        ]);
        $this->createBonusTier($bonusType, [
            'level'          => 3,
            'label'          => 'Gold',
            'qualifier_type' => 'rank',
            'qualifier_value'=> 3,
            'amount'         => '500.0000',
        ]);

        // Affiliate achieved rank 3 (Gold) directly — never had Bronze or Silver
        $commissionResults = collect([
            $this->makeCommissionResult([
                'user_id' => $affiliate->id,
                'qualification_snapshot' => [
                    'current_rank' => 3,
                ],
            ]),
        ]);

        $affiliates = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->company->id)
            ->where('role', 'affiliate')
            ->get();

        $results = $this->calculator->calculate($bonusType, $affiliates, $commissionResults, $this->today);

        // Filter to only this affiliate's entries
        $affiliateEntries = $results->filter(fn ($e) => $e->user_id === $affiliate->id);

        // Exactly one entry should be created for the highest achieved rank
        $totalPaid = $affiliateEntries->reduce(
            fn (string $carry, $entry) => bcadd($carry, (string) $entry->amount, 4),
            '0'
        );

        // Only Gold ($500) should be paid — not Bronze+Silver+Gold ($900)
        $this->assertBonusEquals('500.0000', $totalPaid,
            'Skipped ranks pay only the highest achieved rank bonus, not cumulative');
    }

    /**
     * @test
     *
     * Affiliate's current_rank in the qualification snapshot is null or 0,
     * meaning no rank was achieved. Calculator must return empty collection.
     */
    public function no_rank_achieved_returns_zero(): void
    {
        $affiliate = $this->createAffiliate('Unranked Affiliate');

        $bonusType = $this->createBonusType([
            'type'      => 'rank_advancement',
            'is_active' => true,
        ]);
        $this->createBonusTier($bonusType, [
            'level'          => 1,
            'label'          => 'Bronze',
            'qualifier_type' => 'rank',
            'qualifier_value'=> 1,
            'amount'         => '100.0000',
        ]);

        // No rank in snapshot
        $commissionResults = collect([
            $this->makeCommissionResult([
                'user_id' => $affiliate->id,
                'qualification_snapshot' => [
                    'current_rank' => null,
                ],
            ]),
        ]);

        $affiliates = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->company->id)
            ->where('role', 'affiliate')
            ->get();

        $results = $this->calculator->calculate($bonusType, $affiliates, $commissionResults, $this->today);

        $entry = $results->firstWhere('user_id', $affiliate->id);
        if ($entry !== null) {
            $this->assertBonusEquals('0.0000', (string) $entry->amount);
        } else {
            $this->assertTrue(true, 'No entry when no rank achieved is correct');
        }
    }
}
