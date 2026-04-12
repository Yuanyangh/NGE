<?php

namespace App\DTOs;

readonly class InventoryLoadingResult
{
    public function __construct(
        public int $user_id,
        public string $user_name,
        public string $personal_volume,
        public string $referred_volume,
        public string $total_volume,
        public string $ratio,
        public string $threshold,
        public string $risk_level,
    ) {}
}
