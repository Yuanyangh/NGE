<?php

namespace Tests\Feature\Commission;

use App\Services\Commission\QualificationEvaluator;

class QualificationEvaluatorTest extends CommissionTestCase
{
    private QualificationEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evaluator = new QualificationEvaluator();
        $this->createCompanyWithPlan();
    }

    /** @test — Section 8 Test 8: No Qualification */
    public function affiliate_with_zero_active_customers_earns_nothing(): void
    {
        $affiliate = $this->createAffiliate('Affiliate A');

        // Volume exists but no active customers (no referred_by transactions)
        $customer = $this->createCustomer('Customer', $this->nodeFor($affiliate));
        $this->createTransaction($customer, 500, referredBy: null, daysAgo: 5);

        $result = $this->evaluator->evaluate($affiliate, $this->today, $this->config);

        $this->assertFalse($result->is_qualified);
        $this->assertEquals(0, $result->active_customer_count);
        $this->assertNull($result->affiliate_tier_index);
    }

    /** @test — Edge case: customer with exactly 20 XP counts as active */
    public function customer_with_exactly_20_xp_counts_as_active(): void
    {
        $affiliate = $this->createAffiliate('Affiliate A');
        $customer = $this->createCustomer('Customer', $this->nodeFor($affiliate));
        $this->createTransaction($customer, 20, referredBy: $affiliate, daysAgo: 5);

        $result = $this->evaluator->evaluate($affiliate, $this->today, $this->config);

        $this->assertEquals(1, $result->active_customer_count);
        $this->assertTrue($result->is_qualified);
    }

    /** @test — Edge case: customer with 19 XP does NOT count as active */
    public function customer_with_19_xp_does_not_count_as_active(): void
    {
        $affiliate = $this->createAffiliate('Affiliate A');
        $customer = $this->createCustomer('Customer', $this->nodeFor($affiliate));
        $this->createTransaction($customer, 19, referredBy: $affiliate, daysAgo: 5);

        $result = $this->evaluator->evaluate($affiliate, $this->today, $this->config);

        $this->assertEquals(0, $result->active_customer_count);
        $this->assertFalse($result->is_qualified);
    }

    /** @test — Edge case: transaction outside 30-day window is excluded */
    public function transaction_outside_30_day_window_is_excluded(): void
    {
        $affiliate = $this->createAffiliate('Affiliate A');
        $customer = $this->createCustomer('Customer', $this->nodeFor($affiliate));

        // Transaction 31 days ago — outside window
        $this->createTransaction($customer, 50, referredBy: $affiliate, daysAgo: 31);

        $result = $this->evaluator->evaluate($affiliate, $this->today, $this->config);

        $this->assertEquals(0, $result->active_customer_count);
        $this->assertCommissionEquals('0', $result->referred_volume_30d);
    }

    /** @test — Edge case: transaction at boundary (29 days ago) is included */
    public function transaction_at_29_days_ago_is_included(): void
    {
        $affiliate = $this->createAffiliate('Affiliate A');
        $customer = $this->createCustomer('Customer', $this->nodeFor($affiliate));
        $this->createTransaction($customer, 50, referredBy: $affiliate, daysAgo: 29);

        $result = $this->evaluator->evaluate($affiliate, $this->today, $this->config);

        $this->assertEquals(1, $result->active_customer_count);
        $this->assertCommissionEquals('50', $result->referred_volume_30d);
    }

    /** @test — Edge case: reversed transaction does not count */
    public function reversed_transaction_does_not_count(): void
    {
        $affiliate = $this->createAffiliate('Affiliate A');
        $customer = $this->createCustomer('Customer', $this->nodeFor($affiliate));
        $this->createTransaction($customer, 50, referredBy: $affiliate, daysAgo: 5, status: 'reversed');

        $result = $this->evaluator->evaluate($affiliate, $this->today, $this->config);

        $this->assertEquals(0, $result->active_customer_count);
        $this->assertCommissionEquals('0', $result->referred_volume_30d);
    }

    /** @test — Multiple customers from different users count separately */
    public function counts_distinct_active_customers(): void
    {
        $affiliate = $this->createAffiliate('Affiliate A');

        $c1 = $this->createCustomer('C1', $this->nodeFor($affiliate));
        $c2 = $this->createCustomer('C2', $this->nodeFor($affiliate));
        $c3 = $this->createCustomer('C3', $this->nodeFor($affiliate));

        $this->createTransaction($c1, 25, referredBy: $affiliate, daysAgo: 5);
        $this->createTransaction($c1, 30, referredBy: $affiliate, daysAgo: 3); // Same customer, different txn
        $this->createTransaction($c2, 25, referredBy: $affiliate, daysAgo: 10);
        $this->createTransaction($c3, 25, referredBy: $affiliate, daysAgo: 15);

        $result = $this->evaluator->evaluate($affiliate, $this->today, $this->config);

        $this->assertEquals(3, $result->active_customer_count);
        $this->assertCommissionEquals('105', $result->referred_volume_30d);
    }
}
