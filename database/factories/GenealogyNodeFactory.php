<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\GenealogyNode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GenealogyNode> */
class GenealogyNodeFactory extends Factory
{
    protected $model = GenealogyNode::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'sponsor_id' => null,
            'position' => null,
            'tree_depth' => 0,
        ];
    }

    public function withSponsor(GenealogyNode $sponsor): static
    {
        return $this->state(fn () => [
            'company_id' => $sponsor->company_id,
            'sponsor_id' => $sponsor->id,
            'tree_depth' => $sponsor->tree_depth + 1,
        ]);
    }
}
