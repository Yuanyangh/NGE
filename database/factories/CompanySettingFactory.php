<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanySetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CompanySetting> */
class CompanySettingFactory extends Factory
{
    protected $model = CompanySetting::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
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
