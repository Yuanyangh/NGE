<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use App\Models\WalletAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WalletAccount> */
class WalletAccountFactory extends Factory
{
    protected $model = WalletAccount::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'currency' => 'USD',
        ];
    }
}
