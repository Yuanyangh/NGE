<?php

namespace Database\Factories;

use App\Models\BonusLedgerEntry;
use App\Models\BonusType;
use App\Models\CommissionRun;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BonusLedgerEntry> */
class BonusLedgerEntryFactory extends Factory
{
    protected $model = BonusLedgerEntry::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'commission_run_id' => CommissionRun::factory(),
            'user_id' => User::factory(),
            'bonus_type_id' => BonusType::factory(),
            'amount' => fake()->randomFloat(4, 1, 500),
            'tier_achieved' => fake()->optional()->numberBetween(1, 5),
            'qualification_snapshot' => null,
            'description' => fake()->optional()->sentence(),
            'created_at' => now(),
        ];
    }

    public function withSnapshot(array $snapshot): static
    {
        return $this->state(fn () => ['qualification_snapshot' => $snapshot]);
    }
}
