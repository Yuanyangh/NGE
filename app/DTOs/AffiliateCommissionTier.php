<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

class AffiliateCommissionTier extends Data
{
    public function __construct(
        public readonly int $min_active_customers,
        public readonly float $min_referred_volume,
        public readonly float $rate,
    ) {}
}
