<?php

namespace App\DTOs;

readonly class BonusResult
{
    public function __construct(
        public int $user_id,
        public string $amount,      // bcmath string for precision
        public int $bonus_type_id,
        public ?int $tier_achieved,
        public array $qualification_snapshot,
        public string $description,
    ) {}
}
