<?php

namespace App\Services\Simulator;

use App\DTOs\DayProjection;
use App\DTOs\PayoutBreakdown;
use App\DTOs\SimulationConfig;
use App\DTOs\SimulationResult;

class SimulatorReportBuilder
{
    /**
     * Compile daily projections into a full simulation result.
     *
     * @param DayProjection[] $dailyProjections
     * @param array $affiliateEarnings Keyed by affiliate ID: ['affiliate' => string, 'viral' => string]
     * @param array $tierAccumulator Accumulates tier distribution across all days
     */
    public function build(
        array $dailyProjections,
        SimulationConfig $config,
        array $affiliateEarnings,
        array $tierAccumulator,
    ): SimulationResult {
        $summary = $this->buildSummary($dailyProjections, $affiliateEarnings);
        $payoutBreakdown = $this->buildPayoutBreakdown($affiliateEarnings, $tierAccumulator);
        $riskIndicators = $this->buildRiskIndicators($dailyProjections, $summary);

        return new SimulationResult(
            summary: $summary,
            daily_projections: $dailyProjections,
            payout_breakdown: $payoutBreakdown,
            risk_indicators: $riskIndicators,
        );
    }

    private function buildSummary(array $dailyProjections, array $affiliateEarnings): array
    {
        $totalVolume = '0';
        $totalAffiliate = '0';
        $totalViral = '0';
        $totalPayout = '0';
        $viralCapDays = 0;
        $globalCapDays = 0;

        foreach ($dailyProjections as $dp) {
            $totalVolume = bcadd($totalVolume, $dp->daily_volume, 4);
            $totalAffiliate = bcadd($totalAffiliate, $dp->affiliate_commissions, 4);
            $totalViral = bcadd($totalViral, $dp->viral_commissions, 4);
            $totalPayout = bcadd($totalPayout, $dp->total_payout, 4);

            if ($dp->viral_cap_applied) {
                $viralCapDays++;
            }
            if ($dp->global_cap_applied) {
                $globalCapDays++;
            }
        }

        $projectionDays = count($dailyProjections);
        $payoutRatio = bccomp($totalVolume, '0', 4) > 0
            ? bcmul(bcdiv($totalPayout, $totalVolume, 6), '100', 2)
            : '0.00';

        // Per-affiliate stats
        $affiliateCount = count($affiliateEarnings);
        $avgEarningPerDay = $affiliateCount > 0 && $projectionDays > 0
            ? bcdiv($totalPayout, (string) ($affiliateCount * $projectionDays), 2)
            : '0.00';

        // Top earner
        $topEarnerTotal = '0';
        foreach ($affiliateEarnings as $earnings) {
            $total = bcadd($earnings['affiliate'], $earnings['viral'], 4);
            if (bccomp($total, $topEarnerTotal, 4) > 0) {
                $topEarnerTotal = $total;
            }
        }

        $topEarnerDaily = $projectionDays > 0
            ? bcdiv($topEarnerTotal, (string) $projectionDays, 2)
            : '0.00';

        $topEarnerConcentration = bccomp($totalPayout, '0', 4) > 0
            ? bcmul(bcdiv($topEarnerTotal, $totalPayout, 6), '100', 2)
            : '0.00';

        return [
            'projection_days' => $projectionDays,
            'total_projected_volume' => $totalVolume,
            'total_affiliate_commissions' => $totalAffiliate,
            'total_viral_commissions' => $totalViral,
            'total_payout' => $totalPayout,
            'payout_ratio_percent' => (float) $payoutRatio,
            'viral_cap_triggered_days' => $viralCapDays,
            'global_cap_triggered_days' => $globalCapDays,
            'average_affiliate_earning_per_day' => (float) $avgEarningPerDay,
            'top_earner_daily_average' => (float) $topEarnerDaily,
            'top_earner_concentration_percent' => (float) $topEarnerConcentration,
        ];
    }

    private function buildPayoutBreakdown(array $affiliateEarnings, array $tierAccumulator): PayoutBreakdown
    {
        $totalAffiliate = '0';
        $totalViral = '0';
        $totalVolume = '0';

        foreach ($affiliateEarnings as $earnings) {
            $totalAffiliate = bcadd($totalAffiliate, $earnings['affiliate'], 4);
            $totalViral = bcadd($totalViral, $earnings['viral'], 4);
        }

        $totalPayout = bcadd($totalAffiliate, $totalViral, 4);

        // By commission type
        $byType = [
            'affiliate' => [
                'total' => $totalAffiliate,
                'percent_of_payout' => bccomp($totalPayout, '0', 4) > 0
                    ? (float) bcmul(bcdiv($totalAffiliate, $totalPayout, 6), '100', 2)
                    : 0,
            ],
            'viral' => [
                'total' => $totalViral,
                'percent_of_payout' => bccomp($totalPayout, '0', 4) > 0
                    ? (float) bcmul(bcdiv($totalViral, $totalPayout, 6), '100', 2)
                    : 0,
            ],
        ];

        return new PayoutBreakdown(
            by_commission_type: $byType,
            affiliate_tier_distribution: $tierAccumulator['affiliate_tiers'] ?? [],
            viral_tier_distribution: $tierAccumulator['viral_tiers'] ?? [],
        );
    }

    private function buildRiskIndicators(array $dailyProjections, array $summary): array
    {
        $projectionDays = count($dailyProjections);

        // Payout ratio trend (compare first quarter to last quarter)
        $trend = 'stable';
        if ($projectionDays >= 8) {
            $quarter = (int) ceil($projectionDays / 4);
            $firstQuarter = array_slice($dailyProjections, 0, $quarter);
            $lastQuarter = array_slice($dailyProjections, -$quarter);

            $firstAvg = $this->averagePayoutRatio($firstQuarter);
            $lastAvg = $this->averagePayoutRatio($lastQuarter);

            $diff = $lastAvg - $firstAvg;
            if ($diff > 2.0) {
                $trend = 'increasing';
            } elseif ($diff < -2.0) {
                $trend = 'decreasing';
            }
        }

        // Cap trigger frequency
        $capDays = $summary['viral_cap_triggered_days'] + $summary['global_cap_triggered_days'];
        $capFrequency = match (true) {
            $capDays === 0 => 'none',
            $capDays <= $projectionDays * 0.05 => 'rare',
            $capDays <= $projectionDays * 0.20 => 'occasional',
            $capDays <= $projectionDays * 0.50 => 'frequent',
            default => 'constant',
        };

        // Top earner concentration
        $concentration = match (true) {
            $summary['top_earner_concentration_percent'] <= 5.0 => 'low',
            $summary['top_earner_concentration_percent'] <= 15.0 => 'moderate',
            $summary['top_earner_concentration_percent'] <= 30.0 => 'high',
            default => 'extreme',
        };

        // Sustainability score (0-100)
        $score = 100;

        // Penalize high payout ratio
        $payoutRatio = $summary['payout_ratio_percent'];
        if ($payoutRatio > 30) {
            $score -= (int) min(30, ($payoutRatio - 30) * 3);
        }

        // Penalize frequent cap triggers
        if ($capFrequency === 'frequent') {
            $score -= 15;
        } elseif ($capFrequency === 'constant') {
            $score -= 25;
        }

        // Penalize increasing payout trend
        if ($trend === 'increasing') {
            $score -= 10;
        }

        // Penalize high concentration
        if ($concentration === 'high') {
            $score -= 10;
        } elseif ($concentration === 'extreme') {
            $score -= 20;
        }

        return [
            'payout_ratio_trend' => $trend,
            'cap_trigger_frequency' => $capFrequency,
            'top_earner_concentration' => $concentration,
            'sustainability_score' => max(0, $score),
        ];
    }

    private function averagePayoutRatio(array $projections): float
    {
        if (empty($projections)) {
            return 0;
        }

        $sum = 0;
        foreach ($projections as $dp) {
            $sum += (float) $dp->payout_ratio_percent;
        }

        return $sum / count($projections);
    }
}
