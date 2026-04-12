<?php

namespace App\DTOs;

readonly class ChurnRiskResult
{
    public function __construct(
        public int $user_id,
        public string $user_name,
        public string $risk_level,
        public string $reason,
        public ?int $days_since_last_order,
        public ?string $current_volume,
        public ?string $previous_volume,
        public ?string $volume_change_pct,
    ) {}
}
