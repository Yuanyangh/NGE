<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

class DayProjection extends Data
{
    public function __construct(
        public readonly int $day,
        public readonly string $date,
        public readonly int $total_affiliates,
        public readonly int $total_customers,
        public readonly int $active_customers,
        public readonly string $daily_volume,
        public readonly string $rolling_30d_volume,
        public readonly string $affiliate_commissions,
        public readonly string $viral_commissions,
        public readonly string $total_payout,
        public readonly string $payout_ratio_percent,
        public readonly bool $viral_cap_applied,
        public readonly bool $global_cap_applied,
    ) {}
}
