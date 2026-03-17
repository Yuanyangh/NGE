<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

class SimulationResult extends Data
{
    public function __construct(
        public readonly array $summary,
        /** @var DayProjection[] */
        public readonly array $daily_projections,
        public readonly PayoutBreakdown $payout_breakdown,
        public readonly array $risk_indicators,
    ) {}

    public function toStorableArray(): array
    {
        return [
            'summary' => $this->summary,
            'daily_projections' => array_map(fn (DayProjection $dp) => [
                'day' => $dp->day,
                'date' => $dp->date,
                'total_affiliates' => $dp->total_affiliates,
                'total_customers' => $dp->total_customers,
                'active_customers' => $dp->active_customers,
                'daily_volume' => $dp->daily_volume,
                'rolling_30d_volume' => $dp->rolling_30d_volume,
                'affiliate_commissions' => $dp->affiliate_commissions,
                'viral_commissions' => $dp->viral_commissions,
                'total_payout' => $dp->total_payout,
                'payout_ratio_percent' => $dp->payout_ratio_percent,
                'viral_cap_applied' => $dp->viral_cap_applied,
                'global_cap_applied' => $dp->global_cap_applied,
            ], $this->daily_projections),
            'payout_breakdown' => [
                'by_commission_type' => $this->payout_breakdown->by_commission_type,
                'affiliate_tier_distribution' => $this->payout_breakdown->affiliate_tier_distribution,
                'viral_tier_distribution' => $this->payout_breakdown->viral_tier_distribution,
            ],
            'risk_indicators' => $this->risk_indicators,
        ];
    }
}
