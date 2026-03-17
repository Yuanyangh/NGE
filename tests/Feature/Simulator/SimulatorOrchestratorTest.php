<?php

namespace Tests\Feature\Simulator;

use App\Models\SimulationRun;
use App\Services\Simulator\SimulatorOrchestrator;

class SimulatorOrchestratorTest extends SimulatorTestCase
{
    /**
     * S1: Zero Growth — flat payout, no tier progression, stable ratio.
     */
    public function test_s1_zero_growth(): void
    {
        $this->createCompanyWithPlan();

        $config = $this->makeSimConfig([
            'projection_days' => 10,
            'starting_affiliates' => 5,
            'starting_customers' => 20,
            'growth' => [
                'new_affiliates_per_day' => 0,
                'new_customers_per_affiliate_per_month' => 0,
                'affiliate_to_customer_ratio' => 0,
            ],
            'retention' => [
                'customer_monthly_churn_rate' => 0,
                'affiliate_monthly_churn_rate' => 0,
            ],
        ]);

        $orchestrator = app(SimulatorOrchestrator::class);
        $result = $orchestrator->run($this->company, $this->plan, $config, 'Zero Growth');

        $this->assertCount(10, $result->daily_projections);

        // Affiliate and customer counts should remain constant (no growth, no churn)
        $firstDay = $result->daily_projections[0];
        $lastDay = $result->daily_projections[9];
        $this->assertEquals($firstDay->total_affiliates, $lastDay->total_affiliates);
        $this->assertEquals($firstDay->total_customers, $lastDay->total_customers);

        // Payout ratio should be stable
        $this->assertEquals('stable', $result->risk_indicators['payout_ratio_trend']);

        // Simulation should be persisted
        $this->assertDatabaseHas('simulation_runs', [
            'company_id' => $this->company->id,
            'name' => 'Zero Growth',
            'status' => 'completed',
        ]);
    }

    /**
     * S2: High Growth — increasing payout, tier progression, check cap triggers.
     */
    public function test_s2_high_growth(): void
    {
        $this->createCompanyWithPlan();

        $config = $this->makeSimConfig([
            'projection_days' => 15,
            'starting_affiliates' => 5,
            'starting_customers' => 20,
            'growth' => [
                'new_affiliates_per_day' => 3,
                'new_customers_per_affiliate_per_month' => 5,
                'affiliate_to_customer_ratio' => 0.15,
            ],
        ]);

        $orchestrator = app(SimulatorOrchestrator::class);
        $result = $orchestrator->run($this->company, $this->plan, $config, 'High Growth');

        $this->assertCount(15, $result->daily_projections);

        // Network should grow
        $lastDay = $result->daily_projections[14];
        $this->assertGreaterThan(5, $lastDay->total_affiliates);

        // Total payout and volume should be > 0
        $this->assertGreaterThan(0, (float) $result->summary['total_payout']);
        $this->assertGreaterThan(0, (float) $result->summary['total_projected_volume']);

        // Payout ratio should be reasonable (not > 100%)
        $this->assertLessThan(100, $result->summary['payout_ratio_percent']);
    }

    /**
     * S3: High Churn — network shrinks, payouts decline.
     */
    public function test_s3_high_churn(): void
    {
        $this->createCompanyWithPlan();

        $config = $this->makeSimConfig([
            'projection_days' => 15,
            'starting_affiliates' => 15,
            'starting_customers' => 60,
            'growth' => [
                'new_affiliates_per_day' => 0,
                'new_customers_per_affiliate_per_month' => 0,
            ],
            'retention' => [
                'customer_monthly_churn_rate' => 0.30,
                'affiliate_monthly_churn_rate' => 0.20,
            ],
        ]);

        $orchestrator = app(SimulatorOrchestrator::class);
        $result = $orchestrator->run($this->company, $this->plan, $config, 'High Churn');

        $this->assertCount(15, $result->daily_projections);

        // Network should shrink (fewer active customers at end than start)
        $firstDay = $result->daily_projections[0];
        $lastDay = $result->daily_projections[14];
        $this->assertLessThanOrEqual($firstDay->total_customers, $lastDay->total_customers);

        // Daily volume should decline as active customers churn out
        $this->assertGreaterThanOrEqual(
            (float) $lastDay->daily_volume,
            (float) $firstDay->daily_volume,
            'Daily volume should decline or stay flat with high churn'
        );

        // Risk indicators should be populated
        $this->assertArrayHasKey('payout_ratio_trend', $result->risk_indicators);
        $this->assertArrayHasKey('sustainability_score', $result->risk_indicators);
    }

    /**
     * S4: Imbalanced Tree — QVV caps reduce viral payouts, lower sustainability.
     */
    public function test_s4_imbalanced_tree(): void
    {
        $this->createCompanyWithPlan();

        $config = $this->makeSimConfig([
            'projection_days' => 10,
            'starting_affiliates' => 8,
            'starting_customers' => 30,
            'tree_shape' => [
                'average_legs_per_affiliate' => 2,
                'leg_balance_ratio' => 0.1, // mega-leg
                'depth_bias' => 'deep',
            ],
        ]);

        $orchestrator = app(SimulatorOrchestrator::class);
        $result = $orchestrator->run($this->company, $this->plan, $config, 'Imbalanced Tree');

        $this->assertCount(10, $result->daily_projections);
        $this->assertArrayHasKey('total_payout', $result->summary);
        $this->assertArrayHasKey('sustainability_score', $result->risk_indicators);

        $score = $result->risk_indicators['sustainability_score'];
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    /**
     * S5: Cap Stress Test — viral commissions pushed past 15%.
     */
    public function test_s5_cap_stress_test(): void
    {
        // Very generous viral rewards with low thresholds
        $this->createCompanyWithPlan([
            'viral_commission' => [
                'tiers' => [
                    ['tier' => 1, 'min_active_customers' => 1, 'min_referred_volume' => 0, 'min_qvv' => 0, 'daily_reward' => 200.00],
                ],
            ],
        ]);

        $config = $this->makeSimConfig([
            'projection_days' => 10,
            'starting_affiliates' => 10,
            'starting_customers' => 40,
            'growth' => [
                'new_affiliates_per_day' => 2,
                'new_customers_per_affiliate_per_month' => 3,
            ],
            'transactions' => [
                'average_order_xp' => 25,
                'orders_per_customer_per_month' => 1.0,
            ],
        ]);

        $orchestrator = app(SimulatorOrchestrator::class);
        $result = $orchestrator->run($this->company, $this->plan, $config, 'Cap Stress');

        // Viral cap should trigger on at least some days
        $this->assertGreaterThan(0, $result->summary['viral_cap_triggered_days']);
        $this->assertNotEquals('none', $result->risk_indicators['cap_trigger_frequency']);
    }

    /**
     * S6: Plan Comparison — same config, two different plans produce different results.
     */
    public function test_s6_plan_comparison(): void
    {
        $this->createCompanyWithPlan();

        // Create a second plan with a single 20% tier
        $config2 = $this->soCommConfig();
        $config2['affiliate_commission']['tiers'] = [
            ['min_active_customers' => 1, 'min_referred_volume' => 0, 'rate' => 0.20],
        ];
        $plan2 = \App\Models\CompensationPlan::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Generous Plan',
            'version' => '2.0',
            'config' => $config2,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        $simConfig = $this->makeSimConfig([
            'projection_days' => 10,
            'starting_affiliates' => 5,
            'starting_customers' => 20,
            'seed' => 42,
        ]);

        $orchestrator = app(SimulatorOrchestrator::class);

        $result1 = $orchestrator->run($this->company, $this->plan, $simConfig, 'Plan A');
        $result2 = $orchestrator->run($this->company, $plan2, $simConfig, 'Plan B');

        $this->assertCount(10, $result1->daily_projections);
        $this->assertCount(10, $result2->daily_projections);

        // Plan B (flat 20%) should pay higher affiliate commissions
        $this->assertGreaterThan(
            (float) $result1->summary['total_affiliate_commissions'],
            (float) $result2->summary['total_affiliate_commissions']
        );

        // Both should be persisted
        $this->assertEquals(2, SimulationRun::withoutGlobalScopes()
            ->where('company_id', $this->company->id)
            ->where('status', 'completed')
            ->count()
        );
    }

    /**
     * S7: Deterministic Seeding — same config + seed = identical results.
     */
    public function test_s7_deterministic_seeding(): void
    {
        $this->createCompanyWithPlan();

        $config = $this->makeSimConfig([
            'projection_days' => 10,
            'starting_affiliates' => 5,
            'starting_customers' => 20,
            'seed' => 12345,
        ]);

        $orchestrator = app(SimulatorOrchestrator::class);

        $result1 = $orchestrator->run($this->company, $this->plan, $config, 'Run A');
        $result2 = $orchestrator->run($this->company, $this->plan, $config, 'Run B');

        // Results must be identical
        $this->assertEquals(
            $result1->summary['total_payout'],
            $result2->summary['total_payout'],
        );
        $this->assertEquals(
            $result1->summary['total_projected_volume'],
            $result2->summary['total_projected_volume'],
        );
        $this->assertEquals(
            $result1->summary['total_affiliate_commissions'],
            $result2->summary['total_affiliate_commissions'],
        );
        $this->assertEquals(
            $result1->summary['total_viral_commissions'],
            $result2->summary['total_viral_commissions'],
        );

        // Day-by-day comparison
        for ($i = 0; $i < 10; $i++) {
            $this->assertEquals(
                $result1->daily_projections[$i]->daily_volume,
                $result2->daily_projections[$i]->daily_volume,
                "Day {$i} volume should match"
            );
            $this->assertEquals(
                $result1->daily_projections[$i]->total_affiliates,
                $result2->daily_projections[$i]->total_affiliates,
                "Day {$i} affiliate count should match"
            );
        }
    }
}
