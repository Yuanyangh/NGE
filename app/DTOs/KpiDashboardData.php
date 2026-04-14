<?php

namespace App\DTOs;

readonly class KpiDashboardData
{
    public function __construct(
        // Current period metrics
        public string $totalVolume,
        public string $totalCommissions,
        public string $totalBonuses,
        public string $payoutRatio,
        public int $activeAffiliates,
        public int $totalAffiliates,
        public int $activeCustomers,
        public int $newEnrollments,
        public int $commissionRunCount,
        public int $viralCapTriggeredCount,

        // Top earners: [{user_id, name, total_earnings}]
        public array $topEarners,

        // Trends for charts: [{date, amount}]
        public array $volumeTrend,
        public array $payoutTrend,

        // Period-over-period % change (bcmath strings, can be negative)
        public string $volumeChange,
        public string $commissionChange,
        public string $affiliateChange,
        public string $enrollmentChange,

        // Period info
        public string $periodStart,
        public string $periodEnd,
    ) {}
}
