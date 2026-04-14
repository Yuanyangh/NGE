<?php

namespace App\DTOs;

readonly class BreakageData
{
    public function __construct(
        // Wasted volume (orders below XP threshold)
        public string $wastedVolumeXp,
        public int $wastedTransactionCount,
        public string $qualifyingVolumeXp,
        public string $wastedPercentage,
        public int $xpThreshold,

        // Cap reductions
        public string $viralCapReduction,
        public string $globalCapReduction,
        public string $totalCapReduction,
        public int $viralCapTriggerCount,
        public int $globalCapTriggerCount,

        // Clawbacks
        public string $clawbackTotal,
        public int $clawbackCount,

        // Overall breakage rate
        public string $breakageRate,

        // Period info
        public string $periodStart,
        public string $periodEnd,
    ) {}
}
