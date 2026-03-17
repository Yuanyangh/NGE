<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

class TierProgressData extends Data
{
    public function __construct(
        // Affiliate commission progress
        public readonly ?float $current_affiliate_rate,
        public readonly ?float $next_affiliate_rate,
        public readonly int $current_customers,
        public readonly string $current_volume,
        public readonly ?int $next_affiliate_min_customers,
        public readonly ?float $next_affiliate_min_volume,
        public readonly int $customers_needed,
        public readonly string $volume_needed,
        public readonly float $customer_progress_percent,
        public readonly float $volume_progress_percent,

        // Viral commission progress
        public readonly ?int $current_viral_tier,
        public readonly ?float $current_viral_daily_reward,
        public readonly ?int $next_viral_tier,
        public readonly ?float $next_viral_daily_reward,
        public readonly string $current_qvv,
        public readonly ?float $next_viral_min_qvv,
        public readonly string $qvv_needed,
        public readonly float $qvv_progress_percent,
        public readonly bool $at_max_affiliate_tier,
        public readonly bool $at_max_viral_tier,
    ) {}
}
