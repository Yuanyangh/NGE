<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompensationPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CompensationPlan> */
class CompensationPlanFactory extends Factory
{
    protected $model = CompensationPlan::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => 'Test Plan',
            'version' => '1.0',
            'config' => $this->minimalConfig(),
            'effective_from' => '2026-01-01',
            'effective_until' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    private function minimalConfig(): array
    {
        return [
            'plan' => [
                'name' => 'Test Plan',
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
                'affiliate_inactivity_downgrade_months' => 12,
                'affiliate_inactivity_requires_no_orders' => true,
                'affiliate_inactivity_requires_no_rewards' => true,
            ],
            'affiliate_commission' => [
                'type' => 'tiered_percentage',
                'payout_method' => 'daily_new_volume',
                'basis' => 'referred_volume_30d',
                'customer_basis' => 'referred_active_customers_30d',
                'self_purchase_earns_commission' => false,
                'includes_smartship' => true,
                'tiers' => [
                    ['min_active_customers' => 1, 'min_referred_volume' => 0, 'rate' => 0.10],
                ],
            ],
            'viral_commission' => [
                'type' => 'tiered_fixed_daily',
                'basis' => 'qualifying_viral_volume_30d',
                'tree' => 'enrollment',
                'qvv_algorithm' => [
                    'description' => 'Large leg cap with 2/3 small leg benchmark',
                ],
                'tiers' => [
                    ['tier' => 1, 'min_active_customers' => 2, 'min_referred_volume' => 50, 'min_qvv' => 100, 'daily_reward' => 0.53],
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
