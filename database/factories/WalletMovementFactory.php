<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\WalletAccount;
use App\Models\WalletMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WalletMovement> */
class WalletMovementFactory extends Factory
{
    protected $model = WalletMovement::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'wallet_account_id' => WalletAccount::factory(),
            'type' => 'commission_credit',
            'amount' => fake()->randomFloat(4, 1, 500),
            'status' => 'pending',
            'reference_type' => null,
            'reference_id' => null,
            'description' => null,
            'effective_at' => now(),
            'created_at' => now(),
        ];
    }
}
