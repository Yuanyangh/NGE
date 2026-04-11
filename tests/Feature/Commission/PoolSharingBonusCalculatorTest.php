<?php

namespace Tests\Feature\Commission;

use App\Models\User;
use App\Scopes\CompanyScope;
use App\Services\Commission\Bonus\PoolSharingBonusCalculator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Tests for PoolSharingBonusCalculator.
 *
 * Pool Sharing Bonus allocates a percentage of total company volume for a
 * period into a pool, then distributes that pool among qualifying affiliates.
 *
 * Configuration (bonus_type_configs keys):
 *   pool_percent       — percentage of company volume that goes into the pool
 *   distribution_method — 'equal' | 'volume_weighted'
 *
 * The calculator receives the company's rolling volume for the window via the
 * commission results collection (or derives it itself from the DB). Qualifying
 * affiliates are those present in the commissionResults collection.
 *
 * For volume_weighted: each affiliate's share = their_volume / total_volume * pool.
 *
 * These tests are TDD-first. PoolSharingBonusCalculator does not yet exist.
 */
class PoolSharingBonusCalculatorTest extends BonusTestCase
{
    private PoolSharingBonusCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createCompanyWithPlan();
        $this->calculator = new PoolSharingBonusCalculator();
    }

    /**
     * @test
     *
     * Equal distribution: 5% of $100,000 volume = $5,000 pool.
     * 5 qualifying affiliates with equal shares → $1,000 each.
     *
     * Company volume is injected as a bonus_type_config 'company_volume_period'
     * for test isolation (the calculator reads it from config when provided).
     */
    public function equal_distribution(): void
    {
        // Create 5 affiliates
        $affiliates = collect();
        for ($i = 1; $i <= 5; $i++) {
            $affiliates->push($this->createAffiliate("Affiliate {$i}"));
        }

        $bonusType = $this->createBonusType([
            'type'      => 'pool_sharing',
            'name'      => 'Pool Sharing Bonus',
            'is_active' => true,
        ]);
        $this->createBonusConfig($bonusType, 'pool_percent', '0.05');
        $this->createBonusConfig($bonusType, 'distribution_method', 'equal');
        // Inject company volume for test isolation
        $this->createBonusConfig($bonusType, 'company_volume_override', '100000');

        $commissionResults = collect();
        foreach ($affiliates as $affiliate) {
            $commissionResults->push($this->makeCommissionResult([
                'user_id'              => $affiliate->id,
                'affiliate_commission' => '0',
                'viral_commission'     => '0',
                'qualification_snapshot' => [
                    'referred_volume_30d' => '20000', // equal share of 100k
                ],
            ]));
        }

        $allAffiliates = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->company->id)
            ->where('role', 'affiliate')
            ->get();

        $results = $this->calculator->calculate($bonusType, $allAffiliates, $commissionResults, $this->today);

        $this->assertCount(5, $results, 'All 5 affiliates should receive a pool entry');

        foreach ($affiliates as $affiliate) {
            $entry = $results->firstWhere('user_id', $affiliate->id);
            $this->assertNotNull($entry, "Affiliate {$affiliate->id} should have a pool entry");
            $this->assertBonusEquals('1000.0000', (string) $entry->amount,
                "Each affiliate should receive 1/5 of the $5,000 pool");
        }
    }

    /**
     * @test
     *
     * Volume-weighted distribution: pool = $5,000.
     * Affiliate A: 40% of volume → $2,000.
     * Affiliate B: 30% of volume → $1,500.
     * Affiliate C: 30% of volume → $1,500.
     */
    public function volume_weighted_distribution(): void
    {
        $affiliateA = $this->createAffiliate('Affiliate A');
        $affiliateB = $this->createAffiliate('Affiliate B');
        $affiliateC = $this->createAffiliate('Affiliate C');

        $bonusType = $this->createBonusType([
            'type'      => 'pool_sharing',
            'is_active' => true,
        ]);
        $this->createBonusConfig($bonusType, 'pool_percent', '0.05');
        $this->createBonusConfig($bonusType, 'distribution_method', 'volume_weighted');
        $this->createBonusConfig($bonusType, 'company_volume_override', '100000');

        // A: 40k, B: 30k, C: 30k
        $commissionResults = collect([
            $this->makeCommissionResult([
                'user_id' => $affiliateA->id,
                'qualification_snapshot' => ['referred_volume_30d' => '40000'],
            ]),
            $this->makeCommissionResult([
                'user_id' => $affiliateB->id,
                'qualification_snapshot' => ['referred_volume_30d' => '30000'],
            ]),
            $this->makeCommissionResult([
                'user_id' => $affiliateC->id,
                'qualification_snapshot' => ['referred_volume_30d' => '30000'],
            ]),
        ]);

        $allAffiliates = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->company->id)
            ->where('role', 'affiliate')
            ->get();

        $results = $this->calculator->calculate($bonusType, $allAffiliates, $commissionResults, $this->today);

        $entryA = $results->firstWhere('user_id', $affiliateA->id);
        $entryB = $results->firstWhere('user_id', $affiliateB->id);
        $entryC = $results->firstWhere('user_id', $affiliateC->id);

        $this->assertNotNull($entryA);
        $this->assertNotNull($entryB);
        $this->assertNotNull($entryC);

        // Pool = 100000 * 0.05 = 5000
        // A: 40000/100000 * 5000 = 2000
        $this->assertBonusEquals('2000.0000', (string) $entryA->amount);
        // B: 30000/100000 * 5000 = 1500
        $this->assertBonusEquals('1500.0000', (string) $entryB->amount);
        // C: 30000/100000 * 5000 = 1500
        $this->assertBonusEquals('1500.0000', (string) $entryC->amount);
    }

    /**
     * @test
     *
     * Edge case: no qualifying affiliates in the commission results.
     * The pool should not be distributed. The calculator must return an empty
     * collection without throwing any exception or dividing by zero.
     */
    public function zero_qualifiers_no_error(): void
    {
        $bonusType = $this->createBonusType([
            'type'      => 'pool_sharing',
            'is_active' => true,
        ]);
        $this->createBonusConfig($bonusType, 'pool_percent', '0.05');
        $this->createBonusConfig($bonusType, 'distribution_method', 'equal');
        $this->createBonusConfig($bonusType, 'company_volume_override', '100000');

        // Empty commission results — no qualifying affiliates
        $commissionResults = collect();

        $allAffiliates = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->company->id)
            ->where('role', 'affiliate')
            ->get();

        // Must not throw
        $results = $this->calculator->calculate($bonusType, $allAffiliates, $commissionResults, $this->today);

        $this->assertTrue(
            $results->isEmpty(),
            'Zero qualifiers must produce empty result collection without errors'
        );
    }

    /**
     * @test
     *
     * Single qualifier gets the entire pool.
     * Pool = $5,000, 1 affiliate → gets full $5,000.
     */
    public function single_qualifier_gets_entire_pool(): void
    {
        $affiliate = $this->createAffiliate('Solo Affiliate');

        $bonusType = $this->createBonusType([
            'type'      => 'pool_sharing',
            'is_active' => true,
        ]);
        $this->createBonusConfig($bonusType, 'pool_percent', '0.05');
        $this->createBonusConfig($bonusType, 'distribution_method', 'equal');
        $this->createBonusConfig($bonusType, 'company_volume_override', '100000');

        $commissionResults = collect([
            $this->makeCommissionResult([
                'user_id' => $affiliate->id,
                'qualification_snapshot' => ['referred_volume_30d' => '100000'],
            ]),
        ]);

        $allAffiliates = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->company->id)
            ->where('role', 'affiliate')
            ->get();

        $results = $this->calculator->calculate($bonusType, $allAffiliates, $commissionResults, $this->today);

        $this->assertCount(1, $results);
        $entry = $results->first();
        $this->assertEquals($affiliate->id, $entry->user_id);
        // Single qualifier receives full pool: 100000 * 0.05 = 5000
        $this->assertBonusEquals('5000.0000', (string) $entry->amount);
    }
}
