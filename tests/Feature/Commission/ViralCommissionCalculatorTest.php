<?php

namespace Tests\Feature\Commission;

use App\DTOs\VolumeSnapshot;
use App\Services\Commission\ViralCommissionCalculator;

class ViralCommissionCalculatorTest extends CommissionTestCase
{
    private ViralCommissionCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new ViralCommissionCalculator();
        $this->createCompanyWithPlan();
    }

    /** @test — Section 8 Test 6: Viral Commission Tier Match */
    public function viral_commission_tier_match(): void
    {
        $snapshot = new VolumeSnapshot(
            total_viral_volume: '5000',
            large_leg_volume: '3000',
            small_legs_total: '2000',
            benchmark: '1333.3333',
            capped_large_leg: '1333.3333',
            qualifying_viral_volume: '2500',
            was_capped: true,
            leg_details: [],
        );

        $result = $this->calculator->calculate(
            activeCustomerCount: 2,
            referredVolume: '150',
            volumeSnapshot: $snapshot,
            config: $this->config,
        );

        // Tier 8: 2+ customers, 150+ volume, 2500+ QVV → $12.50
        $this->assertEquals(8, $result['tier']);
        $this->assertCommissionEquals('12.50', $result['amount']);
    }

    /** @test — No tier matched when QVV too low */
    public function no_viral_tier_when_qvv_too_low(): void
    {
        $snapshot = new VolumeSnapshot(
            total_viral_volume: '50',
            large_leg_volume: '50',
            small_legs_total: '0',
            benchmark: '0',
            capped_large_leg: '0',
            qualifying_viral_volume: '50',
            was_capped: true,
            leg_details: [],
        );

        $result = $this->calculator->calculate(
            activeCustomerCount: 2,
            referredVolume: '150',
            volumeSnapshot: $snapshot,
            config: $this->config,
        );

        $this->assertNull($result['tier']);
        $this->assertCommissionEquals('0', $result['amount']);
    }

    /** @test — No tier matched when not enough customers */
    public function no_viral_tier_when_insufficient_customers(): void
    {
        $snapshot = new VolumeSnapshot(
            total_viral_volume: '5000',
            large_leg_volume: '3000',
            small_legs_total: '2000',
            benchmark: '1333',
            capped_large_leg: '1333',
            qualifying_viral_volume: '3333',
            was_capped: true,
            leg_details: [],
        );

        $result = $this->calculator->calculate(
            activeCustomerCount: 1, // Need minimum 2
            referredVolume: '150',
            volumeSnapshot: $snapshot,
            config: $this->config,
        );

        $this->assertNull($result['tier']);
        $this->assertCommissionEquals('0', $result['amount']);
    }
}
