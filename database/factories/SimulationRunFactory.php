<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompensationPlan;
use App\Models\SimulationRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SimulationRun> */
class SimulationRunFactory extends Factory
{
    protected $model = SimulationRun::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'compensation_plan_id' => CompensationPlan::factory(),
            'name' => $this->faker->sentence(3),
            'config' => [
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
            ],
            'results' => null,
            'projection_days' => 30,
            'status' => 'pending',
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ]);
    }
}
