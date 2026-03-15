<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

class VolumeSnapshot extends Data
{
    public function __construct(
        public readonly string $total_viral_volume,
        public readonly string $large_leg_volume,
        public readonly string $small_legs_total,
        public readonly string $benchmark,
        public readonly string $capped_large_leg,
        public readonly string $qualifying_viral_volume,
        public readonly bool $was_capped,
        public readonly array $leg_details,
    ) {}
}
