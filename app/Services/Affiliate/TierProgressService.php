<?php

namespace App\Services\Affiliate;

use App\DTOs\PlanConfig;
use App\DTOs\TierProgressData;
use App\DTOs\VolumeSnapshot;
use App\Models\User;
use App\Services\Commission\LegAggregator;
use App\Services\Commission\QualificationEvaluator;
use App\Services\Commission\QvvCalculator;
use Carbon\Carbon;

class TierProgressService
{
    public function __construct(
        private QualificationEvaluator $qualificationEvaluator,
        private LegAggregator $legAggregator,
        private QvvCalculator $qvvCalculator,
    ) {}

    public function calculate(User $affiliate, Carbon $date, PlanConfig $config): TierProgressData
    {
        $qualification = $this->qualificationEvaluator->evaluate($affiliate, $date, $config);

        $customerCount = $qualification->active_customer_count;
        $referredVolume = $qualification->referred_volume_30d;

        // Compute QVV
        $legVolumes = $this->legAggregator->getLegVolumes($affiliate, $date, $config);
        $volumeSnapshot = $this->qvvCalculator->calculate($legVolumes, $config);
        $currentQvv = $volumeSnapshot->qualifying_viral_volume;

        // Current affiliate tier
        $currentAffiliateRate = $qualification->affiliate_tier_rate;
        $currentAffiliateTierIndex = $qualification->affiliate_tier_index;

        // Next affiliate tier
        $nextAffiliateRate = null;
        $nextAffMinCustomers = null;
        $nextAffMinVolume = null;
        $atMaxAffiliateTier = false;

        if ($currentAffiliateTierIndex !== null && isset($config->affiliate_tiers[$currentAffiliateTierIndex + 1])) {
            $nextTier = $config->affiliate_tiers[$currentAffiliateTierIndex + 1];
            $nextAffiliateRate = $nextTier->rate;
            $nextAffMinCustomers = $nextTier->min_active_customers;
            $nextAffMinVolume = $nextTier->min_referred_volume;
        } elseif ($currentAffiliateTierIndex !== null) {
            $atMaxAffiliateTier = true;
        } else {
            // Not qualified at all — next tier is tier 0
            if (! empty($config->affiliate_tiers)) {
                $nextTier = $config->affiliate_tiers[0];
                $nextAffiliateRate = $nextTier->rate;
                $nextAffMinCustomers = $nextTier->min_active_customers;
                $nextAffMinVolume = $nextTier->min_referred_volume;
            }
        }

        $customersNeeded = $nextAffMinCustomers !== null ? max(0, $nextAffMinCustomers - $customerCount) : 0;
        $volumeNeeded = $nextAffMinVolume !== null ? max(0, bcsub((string) $nextAffMinVolume, $referredVolume, 4)) : '0';

        $customerProgressPct = $this->progressPercent($customerCount, $nextAffMinCustomers);
        $volumeProgressPct = $this->progressPercentBc($referredVolume, $nextAffMinVolume !== null ? (string) $nextAffMinVolume : null);

        // Current viral tier
        $currentViralTier = $qualification->viral_tier;
        $currentViralDailyReward = $qualification->viral_daily_reward;

        // Match viral tier with QVV
        $viralTierIndex = $this->qualificationEvaluator->matchViralTierWithQvv(
            $customerCount, $referredVolume, $currentQvv, $config
        );

        if ($viralTierIndex !== null) {
            $currentViralTier = $config->viral_tiers[$viralTierIndex]->tier;
            $currentViralDailyReward = $config->viral_tiers[$viralTierIndex]->daily_reward;
        }

        // Next viral tier
        $nextViralTier = null;
        $nextViralDailyReward = null;
        $nextViralMinQvv = null;
        $atMaxViralTier = false;

        if ($viralTierIndex !== null && isset($config->viral_tiers[$viralTierIndex + 1])) {
            $next = $config->viral_tiers[$viralTierIndex + 1];
            $nextViralTier = $next->tier;
            $nextViralDailyReward = $next->daily_reward;
            $nextViralMinQvv = $next->min_qvv;
        } elseif ($viralTierIndex !== null) {
            $atMaxViralTier = true;
        } else {
            // Not qualified — next tier is tier 0
            if (! empty($config->viral_tiers)) {
                $next = $config->viral_tiers[0];
                $nextViralTier = $next->tier;
                $nextViralDailyReward = $next->daily_reward;
                $nextViralMinQvv = $next->min_qvv;
            }
        }

        $qvvNeeded = $nextViralMinQvv !== null ? max(0, bcsub((string) $nextViralMinQvv, $currentQvv, 4)) : '0';
        $qvvProgressPct = $this->progressPercentBc($currentQvv, $nextViralMinQvv !== null ? (string) $nextViralMinQvv : null);

        return new TierProgressData(
            current_affiliate_rate: $currentAffiliateRate,
            next_affiliate_rate: $nextAffiliateRate,
            current_customers: $customerCount,
            current_volume: $referredVolume,
            next_affiliate_min_customers: $nextAffMinCustomers,
            next_affiliate_min_volume: $nextAffMinVolume,
            customers_needed: $customersNeeded,
            volume_needed: $volumeNeeded,
            customer_progress_percent: $customerProgressPct,
            volume_progress_percent: $volumeProgressPct,
            current_viral_tier: $currentViralTier,
            current_viral_daily_reward: $currentViralDailyReward,
            next_viral_tier: $nextViralTier,
            next_viral_daily_reward: $nextViralDailyReward,
            current_qvv: $currentQvv,
            next_viral_min_qvv: $nextViralMinQvv,
            qvv_needed: $qvvNeeded,
            qvv_progress_percent: $qvvProgressPct,
            at_max_affiliate_tier: $atMaxAffiliateTier,
            at_max_viral_tier: $atMaxViralTier,
        );
    }

    private function progressPercent(int $current, ?int $target): float
    {
        if ($target === null || $target <= 0) {
            return 100.0;
        }

        return min(100.0, round(($current / $target) * 100, 1));
    }

    private function progressPercentBc(string $current, ?string $target): float
    {
        if ($target === null || bccomp($target, '0', 4) <= 0) {
            return 100.0;
        }

        $pct = bcdiv(bcmul($current, '100', 4), $target, 1);

        return min(100.0, (float) $pct);
    }
}
