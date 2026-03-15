<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

class QualificationResult extends Data
{
    public function __construct(
        public readonly bool $is_qualified,
        public readonly int $active_customer_count,
        public readonly string $referred_volume_30d,
        public readonly ?int $affiliate_tier_index,
        public readonly ?float $affiliate_tier_rate,
        public readonly ?int $viral_tier,
        public readonly ?float $viral_daily_reward,
        public readonly array $reasons,
    ) {}
}
