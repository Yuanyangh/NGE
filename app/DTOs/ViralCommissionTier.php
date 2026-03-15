<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

class ViralCommissionTier extends Data
{
    public function __construct(
        public readonly int $tier,
        public readonly int $min_active_customers,
        public readonly float $min_referred_volume,
        public readonly float $min_qvv,
        public readonly float $daily_reward,
    ) {}
}
