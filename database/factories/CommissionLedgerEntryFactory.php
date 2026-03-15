<?php

namespace Database\Factories;

use App\Models\CommissionLedgerEntry;
use App\Models\CommissionRun;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CommissionLedgerEntry> */
class CommissionLedgerEntryFactory extends Factory
{
    protected $model = CommissionLedgerEntry::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'commission_run_id' => CommissionRun::factory(),
            'user_id' => User::factory(),
            'type' => 'affiliate_commission',
            'amount' => fake()->randomFloat(4, 1, 100),
            'tier_achieved' => 1,
            'qualification_snapshot' => null,
            'description' => null,
            'created_at' => now(),
        ];
    }

    public function viral(): static
    {
        return $this->state(fn () => ['type' => 'viral_commission']);
    }

    public function capAdjustment(): static
    {
        return $this->state(fn () => [
            'type' => 'cap_adjustment',
            'amount' => -abs(fake()->randomFloat(4, 1, 50)),
        ]);
    }
}
