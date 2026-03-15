<?php

namespace Tests\Feature\Commission;

use App\Services\Commission\DirectCommissionCalculator;
use App\Services\Commission\QualificationEvaluator;

class DirectCommissionCalculatorTest extends CommissionTestCase
{
    private DirectCommissionCalculator $calculator;
    private QualificationEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new DirectCommissionCalculator();
        $this->evaluator = new QualificationEvaluator();
        $this->createCompanyWithPlan();
    }

    /** @test — Section 8 Test 1: Basic Affiliate Commission */
    public function basic_affiliate_commission(): void
    {
        $affiliate = $this->createAffiliate('Affiliate A');
        $node = $this->nodeFor($affiliate);

        // 3 active customers, each ordered 25 XP within window (spread across days)
        $c1 = $this->createCustomer('C1', $node);
        $c2 = $this->createCustomer('C2', $node);
        $c3 = $this->createCustomer('C3', $node);

        // Build up rolling volume to 700 XP total (determines tier)
        $this->createTransaction($c1, 25, referredBy: $affiliate, daysAgo: 20);
        $this->createTransaction($c1, 200, referredBy: $affiliate, daysAgo: 15);
        $this->createTransaction($c2, 25, referredBy: $affiliate, daysAgo: 10);
        $this->createTransaction($c2, 200, referredBy: $affiliate, daysAgo: 8);
        $this->createTransaction($c3, 25, referredBy: $affiliate, daysAgo: 5);
        $this->createTransaction($c3, 185, referredBy: $affiliate, daysAgo: 3);

        // Today's new referred transactions: 40 XP
        $this->createTransaction($c1, 40, referredBy: $affiliate, daysAgo: 0);

        // Total rolling: 25+200+25+200+25+185+40 = 700
        $qual = $this->evaluator->evaluate($affiliate, $this->today, $this->config);
        $this->assertEquals(3, $qual->active_customer_count);
        $this->assertCommissionEquals('700', $qual->referred_volume_30d);

        // Tier: 3+ customers, 600+ volume → index 3 = 13%
        $this->assertEquals(3, $qual->affiliate_tier_index);
        $this->assertEquals(0.13, $qual->affiliate_tier_rate);

        // Commission on today's 40 XP only
        $commission = $this->calculator->calculate($affiliate, $this->today, $this->config, 3, 0.13);
        $this->assertCommissionEquals('5.2000', $commission);
    }

    /** @test — Section 8 Test 2: Affiliate Commission Tier Boundary */
    public function affiliate_commission_tier_boundary(): void
    {
        $affiliate = $this->createAffiliate('Affiliate A');
        $node = $this->nodeFor($affiliate);

        $c1 = $this->createCustomer('C1', $node);
        $c2 = $this->createCustomer('C2', $node);

        // Rolling volume: 199 XP (just below tier 2's 200 threshold)
        $this->createTransaction($c1, 100, referredBy: $affiliate, daysAgo: 10);
        $this->createTransaction($c2, 69, referredBy: $affiliate, daysAgo: 5);

        // Today: 30 XP (total becomes 199)
        $this->createTransaction($c1, 30, referredBy: $affiliate, daysAgo: 0);

        $qual = $this->evaluator->evaluate($affiliate, $this->today, $this->config);
        $this->assertEquals(2, $qual->active_customer_count);
        $this->assertCommissionEquals('199', $qual->referred_volume_30d);

        // Only tier 1 matches: 1+ customers, 0+ volume → 10%
        $this->assertEquals(0, $qual->affiliate_tier_index);
        $this->assertEquals(0.10, $qual->affiliate_tier_rate);

        $commission = $this->calculator->calculate($affiliate, $this->today, $this->config, 0, 0.10);
        $this->assertCommissionEquals('3.0000', $commission);
    }

    /** @test — Self-purchases do not earn affiliate commission */
    public function self_purchases_do_not_earn_commission(): void
    {
        $affiliate = $this->createAffiliate('Affiliate A');
        $node = $this->nodeFor($affiliate);

        // One real customer to qualify
        $c1 = $this->createCustomer('C1', $node);
        $this->createTransaction($c1, 25, referredBy: $affiliate, daysAgo: 5);

        // Affiliate buys from themselves today — should NOT count
        $this->createTransaction($affiliate, 100, referredBy: $affiliate, daysAgo: 0);

        $todayVolume = $this->calculator->getTodaysNewVolume($affiliate, $this->today, $this->config);

        // Self-purchase excluded, only includes non-self referred transactions
        // Today only has the self-purchase, so volume = 0 (the c1 txn is 5 days ago)
        $this->assertCommissionEquals('0', $todayVolume);
    }
}
