<?php

namespace Tests\Feature\Simulator;

use App\DTOs\PlanConfig;
use App\DTOs\SimulationConfig;
use App\Models\Company;
use App\Models\CompensationPlan;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class SimulatorTestCase extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected CompensationPlan $plan;
    protected PlanConfig $planConfig;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::create(2026, 3, 15));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function createCompanyWithPlan(array $configOverrides = []): void
    {
        $this->company = Company::factory()->create([
            'name' => 'Sim Test Co',
            'slug' => 'sim-test',
        ]);

        $config = $this->soCommConfig();
        $config = array_replace_recursive($config, $configOverrides);

        $this->plan = CompensationPlan::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Sim Test Plan',
            'version' => '1.0',
            'config' => $config,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        $this->planConfig = PlanConfig::fromArray($config);
    }

    protected function makeSimConfig(array $overrides = []): SimulationConfig
    {
        $defaults = [
            'projection_days' => 30,
            'starting_affiliates' => 10,
            'starting_customers' => 40,
            'seed' => 42,
            'growth' => [
                'new_affiliates_per_day' => 2,
                'new_customers_per_affiliate_per_month' => 3,
                'affiliate_to_customer_ratio' => 0.15,
                'growth_curve' => 'linear',
            ],
            'transactions' => [
                'average_order_xp' => 45,
                'orders_per_customer_per_month' => 1.5,
                'smartship_adoption_rate' => 0.30,
                'smartship_average_xp' => 35,
                'refund_rate' => 0.05,
            ],
            'retention' => [
                'customer_monthly_churn_rate' => 0.08,
                'affiliate_monthly_churn_rate' => 0.05,
            ],
            'tree_shape' => [
                'average_legs_per_affiliate' => 3,
                'leg_balance_ratio' => 0.6,
                'depth_bias' => 'moderate',
            ],
        ];

        return SimulationConfig::fromArray(array_replace_recursive($defaults, $overrides));
    }

    protected function soCommConfig(): array
    {
        return [
            'plan' => [
                'name' => 'SoComm Affiliate Rewards Program',
                'version' => '1.0',
                'effective_date' => '2026-01-01',
                'currency' => 'USD',
                'calculation_frequency' => 'daily',
                'credit_frequency' => 'weekly',
                'day_definition' => [
                    'start' => '00:00:00',
                    'end' => '23:59:59',
                    'timezone' => 'UTC',
                ],
            ],
            'qualification' => [
                'rolling_days' => 30,
                'active_customer_min_order_xp' => 20,
                'active_customer_threshold_type' => 'per_order',
            ],
            'affiliate_commission' => [
                'type' => 'tiered_percentage',
                'payout_method' => 'daily_new_volume',
                'basis' => 'referred_volume_30d',
                'customer_basis' => 'referred_active_customers_30d',
                'self_purchase_earns_commission' => false,
                'includes_smartship' => true,
                'tiers' => [
                    ['min_active_customers' => 1, 'min_referred_volume' => 0,    'rate' => 0.10],
                    ['min_active_customers' => 2, 'min_referred_volume' => 200,  'rate' => 0.11],
                    ['min_active_customers' => 2, 'min_referred_volume' => 400,  'rate' => 0.12],
                    ['min_active_customers' => 3, 'min_referred_volume' => 600,  'rate' => 0.13],
                    ['min_active_customers' => 4, 'min_referred_volume' => 800,  'rate' => 0.14],
                    ['min_active_customers' => 5, 'min_referred_volume' => 1000, 'rate' => 0.15],
                    ['min_active_customers' => 6, 'min_referred_volume' => 1200, 'rate' => 0.16],
                    ['min_active_customers' => 7, 'min_referred_volume' => 1400, 'rate' => 0.17],
                    ['min_active_customers' => 8, 'min_referred_volume' => 1600, 'rate' => 0.18],
                    ['min_active_customers' => 9, 'min_referred_volume' => 1800, 'rate' => 0.19],
                    ['min_active_customers' => 10,'min_referred_volume' => 2000, 'rate' => 0.20],
                ],
            ],
            'viral_commission' => [
                'type' => 'tiered_fixed_daily',
                'basis' => 'qualifying_viral_volume_30d',
                'tree' => 'enrollment',
                'qvv_algorithm' => ['description' => 'Large leg cap with 2/3 small leg benchmark'],
                'tiers' => [
                    ['tier' => 1,  'min_active_customers' => 2, 'min_referred_volume' => 50,   'min_qvv' => 100,   'daily_reward' => 0.53],
                    ['tier' => 2,  'min_active_customers' => 2, 'min_referred_volume' => 100,  'min_qvv' => 250,   'daily_reward' => 1.33],
                    ['tier' => 3,  'min_active_customers' => 2, 'min_referred_volume' => 100,  'min_qvv' => 500,   'daily_reward' => 2.67],
                    ['tier' => 4,  'min_active_customers' => 2, 'min_referred_volume' => 100,  'min_qvv' => 750,   'daily_reward' => 4.00],
                    ['tier' => 5,  'min_active_customers' => 2, 'min_referred_volume' => 100,  'min_qvv' => 1000,  'daily_reward' => 5.00],
                    ['tier' => 6,  'min_active_customers' => 2, 'min_referred_volume' => 150,  'min_qvv' => 1500,  'daily_reward' => 7.50],
                    ['tier' => 7,  'min_active_customers' => 2, 'min_referred_volume' => 150,  'min_qvv' => 2000,  'daily_reward' => 10.00],
                    ['tier' => 8,  'min_active_customers' => 2, 'min_referred_volume' => 150,  'min_qvv' => 2500,  'daily_reward' => 12.50],
                    ['tier' => 9,  'min_active_customers' => 2, 'min_referred_volume' => 150,  'min_qvv' => 3500,  'daily_reward' => 17.50],
                    ['tier' => 10, 'min_active_customers' => 2, 'min_referred_volume' => 200,  'min_qvv' => 5000,  'daily_reward' => 23.33],
                ],
            ],
            'caps' => [
                'total_payout_cap_percent' => 0.35,
                'total_payout_cap_enforcement' => 'proportional_reduction',
                'total_payout_cap_window' => 'rolling_30d',
                'viral_commission_cap' => [
                    'percent_of_company_volume' => 0.15,
                    'window' => 'rolling_30d',
                    'enforcement' => 'daily_reduction',
                    'reduction_method' => 'proportional_overage',
                ],
                'enforcement_order' => ['viral_cap_first', 'then_global_cap'],
            ],
            'wallet' => [
                'credit_timing' => 'weekly',
                'release_delay_days' => 0,
                'minimum_withdrawal' => 0,
                'clawback_window_days' => 30,
            ],
        ];
    }
}
