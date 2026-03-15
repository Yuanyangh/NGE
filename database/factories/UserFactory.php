<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'customer',
            'status' => 'active',
            'enrolled_at' => null,
            'last_order_at' => null,
            'last_reward_at' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function affiliate(): static
    {
        return $this->state(fn () => [
            'role' => 'affiliate',
            'enrolled_at' => now()->subDays(rand(30, 365)),
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }
}
