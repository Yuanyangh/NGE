<?php

namespace Database\Factories;

use App\Enums\BonusTypeEnum;
use App\Models\BonusType;
use App\Models\Company;
use App\Models\CompensationPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BonusType> */
class BonusTypeFactory extends Factory
{
    protected $model = BonusType::class;

    public function definition(): array
    {
        $type = fake()->randomElement(BonusTypeEnum::cases());

        return [
            'company_id' => Company::factory(),
            'compensation_plan_id' => CompensationPlan::factory(),
            'type' => $type,
            'name' => $type->label(),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
            'priority' => fake()->numberBetween(0, 100),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function matching(): static
    {
        return $this->state(fn () => [
            'type' => BonusTypeEnum::Matching,
            'name' => BonusTypeEnum::Matching->label(),
        ]);
    }

    public function fastStart(): static
    {
        return $this->state(fn () => [
            'type' => BonusTypeEnum::FastStart,
            'name' => BonusTypeEnum::FastStart->label(),
        ]);
    }

    public function rankAdvancement(): static
    {
        return $this->state(fn () => [
            'type' => BonusTypeEnum::RankAdvancement,
            'name' => BonusTypeEnum::RankAdvancement->label(),
        ]);
    }
}
