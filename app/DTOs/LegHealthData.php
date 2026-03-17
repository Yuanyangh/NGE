<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

class LegHealthData extends Data
{
    public function __construct(
        public readonly int $leg_root_user_id,
        public readonly string $leg_root_name,
        public readonly string $volume,
        public readonly int $active_count,
        public readonly string $health_label,
        public readonly bool $is_large_leg,
        public readonly bool $is_capping_qvv,
    ) {}
}
