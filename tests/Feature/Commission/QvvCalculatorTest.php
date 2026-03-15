<?php

namespace Tests\Feature\Commission;

use App\DTOs\PlanConfig;
use App\Services\Commission\QvvCalculator;

class QvvCalculatorTest extends CommissionTestCase
{
    private QvvCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new QvvCalculator();
        $this->createCompanyWithPlan();
    }

    /** @test — Section 8 Test 3: QVV Balanced Tree */
    public function qvv_balanced_tree_no_cap(): void
    {
        $legs = [
            ['leg_root_user_id' => 1, 'volume' => '3000'],
            ['leg_root_user_id' => 2, 'volume' => '2500'],
            ['leg_root_user_id' => 3, 'volume' => '2000'],
        ];

        $result = $this->calculator->calculate($legs, $this->config);

        $this->assertCommissionEquals('3000', $result->large_leg_volume);
        $this->assertCommissionEquals('4500', $result->small_legs_total);
        $this->assertCommissionEquals('3000', $result->benchmark); // (2/3) * 4500 = 3000
        $this->assertFalse($result->was_capped);
        $this->assertCommissionEquals('3000', $result->capped_large_leg);
        $this->assertCommissionEquals('7500', $result->qualifying_viral_volume);
    }

    /** @test — Section 8 Test 4: QVV Imbalanced Tree (Cap Triggered) */
    public function qvv_imbalanced_tree_cap_triggered(): void
    {
        $legs = [
            ['leg_root_user_id' => 1, 'volume' => '10000'],
            ['leg_root_user_id' => 2, 'volume' => '2000'],
            ['leg_root_user_id' => 3, 'volume' => '1000'],
        ];

        $result = $this->calculator->calculate($legs, $this->config);

        $this->assertCommissionEquals('10000', $result->large_leg_volume);
        $this->assertCommissionEquals('3000', $result->small_legs_total);
        $this->assertCommissionEquals('2000', $result->benchmark); // (2/3) * 3000 = 2000
        $this->assertTrue($result->was_capped);
        $this->assertCommissionEquals('2000', $result->capped_large_leg);
        $this->assertCommissionEquals('5000', $result->qualifying_viral_volume);
    }

    /** @test — Section 8 Test 5: QVV Single Leg */
    public function qvv_single_leg_gets_zero(): void
    {
        $legs = [
            ['leg_root_user_id' => 1, 'volume' => '5000'],
        ];

        $result = $this->calculator->calculate($legs, $this->config);

        $this->assertCommissionEquals('5000', $result->large_leg_volume);
        $this->assertCommissionEquals('0', $result->small_legs_total);
        $this->assertCommissionEquals('0', $result->benchmark);
        $this->assertTrue($result->was_capped);
        $this->assertCommissionEquals('0', $result->capped_large_leg);
        $this->assertCommissionEquals('0', $result->qualifying_viral_volume);
    }

    /** @test — Edge case: zero legs */
    public function qvv_zero_legs(): void
    {
        $result = $this->calculator->calculate([], $this->config);

        $this->assertCommissionEquals('0', $result->qualifying_viral_volume);
        $this->assertFalse($result->was_capped);
        $this->assertEmpty($result->leg_details);
    }

    /** @test — Edge case: tied large legs */
    public function qvv_tied_large_legs(): void
    {
        $legs = [
            ['leg_root_user_id' => 1, 'volume' => '3000'],
            ['leg_root_user_id' => 2, 'volume' => '3000'],
            ['leg_root_user_id' => 3, 'volume' => '1000'],
        ];

        $result = $this->calculator->calculate($legs, $this->config);

        // With tied legs, first one is picked as large leg
        $this->assertCommissionEquals('3000', $result->large_leg_volume);
        $this->assertCommissionEquals('4000', $result->small_legs_total); // 3000 + 1000
        $this->assertCommissionEquals('2666.6666', $result->benchmark); // (2/3) * 4000

        // 2666.67 < 3000 → cap triggered
        $this->assertTrue($result->was_capped);
        $this->assertCommissionEquals('2666.6666', $result->capped_large_leg);
        $this->assertCommissionEquals('6666.6666', $result->qualifying_viral_volume);
    }

    /** @test — Two equal legs */
    public function qvv_two_equal_legs(): void
    {
        $legs = [
            ['leg_root_user_id' => 1, 'volume' => '1000'],
            ['leg_root_user_id' => 2, 'volume' => '1000'],
        ];

        $result = $this->calculator->calculate($legs, $this->config);

        $this->assertCommissionEquals('1000', $result->large_leg_volume);
        $this->assertCommissionEquals('1000', $result->small_legs_total);
        $this->assertCommissionEquals('666.6666', $result->benchmark); // (2/3) * 1000

        // 666.67 < 1000 → cap triggered
        $this->assertTrue($result->was_capped);
        $this->assertCommissionEquals('666.6666', $result->capped_large_leg);
        $this->assertCommissionEquals('1666.6666', $result->qualifying_viral_volume);
    }
}
