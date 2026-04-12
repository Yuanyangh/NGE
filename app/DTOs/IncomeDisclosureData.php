<?php

namespace App\DTOs;

readonly class IncomeDisclosureData
{
    public function __construct(
        public int $totalAffiliates,
        public int $activeAffiliates,
        public int $inactiveAffiliates,
        public string $medianEarnings,
        public string $meanEarnings,
        public string $totalPaidOut,
        public string $top1PercentThreshold,
        public string $top10PercentThreshold,
        public array $brackets,
        public string $periodStart,
        public string $periodEnd,
        public int $zeroEarnerCount,
        public string $zeroEarnerPercentage,
        public string $activePercentage,
    ) {}
}
