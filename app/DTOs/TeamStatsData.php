<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

class TeamStatsData extends Data
{
    /**
     * @param LegHealthData[] $legs
     */
    public function __construct(
        public readonly int $total_team_size,
        public readonly int $active_affiliates,
        public readonly int $active_customers,
        public readonly string $total_team_volume_30d,
        public readonly array $legs,
        public readonly bool $qvv_capping_warning,
    ) {}
}
