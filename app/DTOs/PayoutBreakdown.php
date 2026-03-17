<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

class PayoutBreakdown extends Data
{
    public function __construct(
        public readonly array $by_commission_type,
        public readonly array $affiliate_tier_distribution,
        public readonly array $viral_tier_distribution,
    ) {}
}
