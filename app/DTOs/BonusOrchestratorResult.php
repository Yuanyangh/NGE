<?php

namespace App\DTOs;

use Illuminate\Support\Collection;

readonly class BonusOrchestratorResult
{
    public function __construct(
        public string $total_bonus_amount,
        public Collection $entries,
        public bool $cap_triggered,
    ) {}
}
