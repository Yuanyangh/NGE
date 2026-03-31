<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

class AffiliateDashboardData extends Data
{
    public function __construct(
        public readonly string $total_earned_30d,
        public readonly string $pending_amount,
        public readonly string $wallet_balance,
        public readonly ?int $current_affiliate_tier,
        public readonly ?float $current_affiliate_rate,
        public readonly ?int $current_viral_tier,
        public readonly ?float $current_viral_daily_reward,
        public readonly array $recent_activity,
    ) {}
}
