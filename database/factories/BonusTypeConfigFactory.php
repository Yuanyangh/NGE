<?php

namespace Database\Factories;

use App\Models\BonusType;
use App\Models\BonusTypeConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BonusTypeConfig> */
class BonusTypeConfigFactory extends Factory
{
    protected $model = BonusTypeConfig::class;

    public function definition(): array
    {
        return [
            'bonus_type_id' => BonusType::factory(),
            'key' => fake()->unique()->slug(2),
            'value' => fake()->word(),
        ];
    }

    public function forKey(string $key, string $value): static
    {
        return $this->state(fn () => [
            'key' => $key,
            'value' => $value,
        ]);
    }
}
