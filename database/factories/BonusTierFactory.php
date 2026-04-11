<?php

namespace Database\Factories;

use App\Models\BonusTier;
use App\Models\BonusType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BonusTier> */
class BonusTierFactory extends Factory
{
    protected $model = BonusTier::class;

    public function definition(): array
    {
        return [
            'bonus_type_id' => BonusType::factory(),
            'level' => fake()->numberBetween(1, 10),
            'label' => fake()->optional()->words(2, true),
            'qualifier_value' => fake()->optional()->randomFloat(2, 0, 5000),
            'qualifier_type' => fake()->optional()->randomElement(['volume', 'rank', 'count', 'xp']),
            'rate' => fake()->optional()->randomFloat(4, 0.01, 0.30),
            'amount' => fake()->optional()->randomFloat(4, 10, 1000),
        ];
    }

    public function rateOnly(float $rate): static
    {
        return $this->state(fn () => [
            'rate' => $rate,
            'amount' => null,
        ]);
    }

    public function amountOnly(float $amount): static
    {
        return $this->state(fn () => [
            'amount' => $amount,
            'rate' => null,
        ]);
    }
}
