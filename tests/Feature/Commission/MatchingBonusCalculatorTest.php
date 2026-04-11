<?php

namespace Tests\Feature\Commission;

use App\Models\BonusType;
use App\Models\User;
use App\Scopes\CompanyScope;
use App\Services\Commission\Bonus\MatchingBonusCalculator;
use Illuminate\Support\Collection;

/**
 * Tests for MatchingBonusCalculator.
 *
 * Matching bonus pays upline affiliates a percentage of their downline's
 * commission per generation. The calculator receives the full collection of
 * commission results so it can traverse the genealogy tree.
 *
 * These tests are written TDD-first. The MatchingBonusCalculator class does not
 * yet exist; tests will be red until calc-engine implements the service.
 */
class MatchingBonusCalculatorTest extends BonusTestCase
{
    private MatchingBonusCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createCompanyWithPlan();
        $this->calculator = new MatchingBonusCalculator();
    }

    /**
     * @test
     *
     * A sponsors B. B earns $1,000 in commissions.
     * Matching bonus is configured at 15% for generation 1.
     * Expected: A receives 1000 * 0.15 = $150.00 matching bonus.
     */
    public function basic_matching_single_generation(): void
    {
        // Build genealogy: A → B
        $affiliateA = $this->createAffiliate('Affiliate A');
        $nodeA      = $this->nodeFor($affiliateA);
        $affiliateB = $this->createAffiliate('Affiliate B', $nodeA);

        // Bonus type: matching, gen1 = 15%
        $bonusType = $this->createBonusType([
            'type'      => 'matching',
            'name'      => 'Matching Bonus',
            'is_active' => true,
        ]);
        $this->createBonusTier($bonusType, [
            'level'          => 1,
            'label'          => 'Generation 1',
            'qualifier_type' => 'generation',
            'qualifier_value'=> 1,
            'rate'           => '0.1500',
        ]);

        // Commission results: B earned $1,000
        $commissionResults = collect([
            $this->makeCommissionResult([
                'user_id'              => $affiliateB->id,
                'affiliate_commission' => '1000.0000',
                'viral_commission'     => '0',
            ]),
        ]);

        $affiliates = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->company->id)
            ->where('role', 'affiliate')
            ->get();

        $results = $this->calculator->calculate($bonusType, $affiliates, $commissionResults, $this->today);

        $this->assertInstanceOf(Collection::class, $results);

        $aBonus = $results->firstWhere('user_id', $affiliateA->id);
        $this->assertNotNull($aBonus, 'Affiliate A should receive a matching bonus entry');
        $this->assertBonusEquals('150.0000', (string) $aBonus->amount);
        $this->assertEquals($bonusType->id, $aBonus->bonus_type_id);
    }

    /**
     * @test
     *
     * Multi-generation matching: A → B → C.
     * B earns $1,000, C earns $500.
     * Gen 1 rate = 15%, Gen 2 rate = 10%.
     * A receives: (1000 * 0.15) + (500 * 0.10) = 150 + 50 = $200.
     */
    public function multi_generation_matching(): void
    {
        // A → B → C
        $affiliateA = $this->createAffiliate('Affiliate A');
        $nodeA      = $this->nodeFor($affiliateA);
        $affiliateB = $this->createAffiliate('Affiliate B', $nodeA);
        $nodeB      = $this->nodeFor($affiliateB);
        $affiliateC = $this->createAffiliate('Affiliate C', $nodeB);

        $bonusType = $this->createBonusType([
            'type'      => 'matching',
            'name'      => 'Multi-Gen Matching',
            'is_active' => true,
        ]);
        // Gen 1 tier
        $this->createBonusTier($bonusType, [
            'level'          => 1,
            'label'          => 'Generation 1',
            'qualifier_type' => 'generation',
            'qualifier_value'=> 1,
            'rate'           => '0.1500',
        ]);
        // Gen 2 tier
        $this->createBonusTier($bonusType, [
            'level'          => 2,
            'label'          => 'Generation 2',
            'qualifier_type' => 'generation',
            'qualifier_value'=> 2,
            'rate'           => '0.1000',
        ]);

        $commissionResults = collect([
            $this->makeCommissionResult([
                'user_id'              => $affiliateB->id,
                'affiliate_commission' => '1000.0000',
                'viral_commission'     => '0',
            ]),
            $this->makeCommissionResult([
                'user_id'              => $affiliateC->id,
                'affiliate_commission' => '500.0000',
                'viral_commission'     => '0',
            ]),
        ]);

        $affiliates = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->company->id)
            ->where('role', 'affiliate')
            ->get();

        $results = $this->calculator->calculate($bonusType, $affiliates, $commissionResults, $this->today);

        $aBonus = $results->firstWhere('user_id', $affiliateA->id);
        $this->assertNotNull($aBonus, 'Affiliate A should receive a matching bonus for two generations');

        // $150 (gen1 from B) + $50 (gen2 from C) = $200
        $this->assertBonusEquals('200.0000', (string) $aBonus->amount);
    }

    /**
     * @test
     *
     * When the downline affiliate earns $0 commission, the matching bonus must
     * also be $0. The calculator must not attempt division or produce NaN/errors.
     */
    public function zero_commission_downline_returns_zero(): void
    {
        $affiliateA = $this->createAffiliate('Affiliate A');
        $nodeA      = $this->nodeFor($affiliateA);
        $affiliateB = $this->createAffiliate('Affiliate B', $nodeA);

        $bonusType = $this->createBonusType([
            'type'      => 'matching',
            'is_active' => true,
        ]);
        $this->createBonusTier($bonusType, [
            'level' => 1,
            'rate'  => '0.1500',
        ]);

        // B earns $0
        $commissionResults = collect([
            $this->makeCommissionResult([
                'user_id'              => $affiliateB->id,
                'affiliate_commission' => '0',
                'viral_commission'     => '0',
            ]),
        ]);

        $affiliates = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->company->id)
            ->where('role', 'affiliate')
            ->get();

        $results = $this->calculator->calculate($bonusType, $affiliates, $commissionResults, $this->today);

        // A either gets no entry or an entry with $0 — either is acceptable
        $aBonus = $results->firstWhere('user_id', $affiliateA->id);
        if ($aBonus !== null) {
            $this->assertBonusEquals('0.0000', (string) $aBonus->amount);
        } else {
            $this->assertTrue(true, 'No entry for $0 bonus is valid');
        }
    }

    /**
     * @test
     *
     * When a bonus type has is_active = false, the calculator must return an
     * empty collection and must not produce any bonus entries.
     */
    public function inactive_bonus_type_returns_zero(): void
    {
        $affiliateA = $this->createAffiliate('Affiliate A');
        $nodeA      = $this->nodeFor($affiliateA);
        $affiliateB = $this->createAffiliate('Affiliate B', $nodeA);

        // Inactive bonus type
        $bonusType = $this->createBonusType([
            'type'      => 'matching',
            'is_active' => false,
        ]);
        $this->createBonusTier($bonusType, [
            'level' => 1,
            'rate'  => '0.1500',
        ]);

        $commissionResults = collect([
            $this->makeCommissionResult([
                'user_id'              => $affiliateB->id,
                'affiliate_commission' => '1000.0000',
            ]),
        ]);

        $affiliates = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->company->id)
            ->where('role', 'affiliate')
            ->get();

        $results = $this->calculator->calculate($bonusType, $affiliates, $commissionResults, $this->today);

        $this->assertTrue(
            $results->isEmpty(),
            'Inactive bonus type must return empty collection'
        );
    }
}
