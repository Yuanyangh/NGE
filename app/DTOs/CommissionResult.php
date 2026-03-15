<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

class CommissionResult extends Data
{
    public function __construct(
        public readonly int $user_id,
        public readonly string $affiliate_commission,
        public readonly ?int $affiliate_tier_index,
        public readonly ?float $affiliate_tier_rate,
        public readonly string $viral_commission,
        public readonly ?int $viral_tier,
        public readonly string $total_commission,
        public readonly QualificationResult $qualification,
        public readonly ?VolumeSnapshot $volume_snapshot,
    ) {}
}
