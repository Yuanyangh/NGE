<?php

namespace Database\Factories;

use App\Models\CommissionRun;
use App\Models\Company;
use App\Models\CompensationPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CommissionRun> */
class CommissionRunFactory extends Factory
{
    protected $model = CommissionRun::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'compensation_plan_id' => CompensationPlan::factory(),
            'run_date' => now()->toDateString(),
            'status' => 'pending',
            'total_affiliate_commission' => 0,
            'total_viral_commission' => 0,
            'total_company_volume' => 0,
            'viral_cap_triggered' => false,
            'viral_cap_reduction_pct' => null,
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
