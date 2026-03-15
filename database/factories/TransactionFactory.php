<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Transaction> */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 20, 200);

        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'referred_by_user_id' => null,
            'type' => 'purchase',
            'amount' => $amount,
            'xp' => $amount,
            'currency' => 'USD',
            'status' => 'confirmed',
            'qualifies_for_commission' => true,
            'transaction_date' => now(),
            'reference' => 'ORD-' . fake()->unique()->numerify('######'),
        ];
    }

    public function smartship(): static
    {
        return $this->state(fn () => ['type' => 'smartship']);
    }

    public function refund(): static
    {
        return $this->state(fn () => [
            'type' => 'refund',
            'amount' => -abs(fake()->randomFloat(2, 20, 200)),
            'xp' => -abs(fake()->randomFloat(2, 20, 200)),
        ]);
    }

    public function reversed(): static
    {
        return $this->state(fn () => ['status' => 'reversed']);
    }

    public function referredBy(User $user): static
    {
        return $this->state(fn () => ['referred_by_user_id' => $user->id]);
    }

    public function onDate(string $date): static
    {
        return $this->state(fn () => ['transaction_date' => $date]);
    }

    public function withXp(float $xp): static
    {
        return $this->state(fn () => ['xp' => $xp, 'amount' => $xp]);
    }
}
