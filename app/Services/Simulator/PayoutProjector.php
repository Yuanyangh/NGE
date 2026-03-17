<?php

namespace App\Services\Simulator;

use App\DTOs\DayProjection;
use App\DTOs\PlanConfig;
use App\Services\Commission\QvvCalculator;
use App\Services\Commission\ViralCommissionCalculator;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PayoutProjector
{
    public function __construct(
        private readonly QvvCalculator $qvvCalculator,
        private readonly ViralCommissionCalculator $viralCalculator,
    ) {}

    /**
     * Compute commissions for a single projected day using Phase 1 calculator services.
     *
     * Operates entirely on in-memory collections — no DB queries.
     *
     * @param Collection $network Synthetic users
     * @param Collection $todayTransactions Today's synthetic transactions
     * @param Collection $allTransactions All synthetic transactions (for rolling window)
     * @param PlanConfig $planConfig Plan configuration
     * @param string $dateString Current date (Y-m-d)
     * @param int $day Day number in projection
     * @param string $rollingViralPaid Rolling 30d viral commissions already paid
     * @param string $rollingAllPaid Rolling 30d all commissions already paid
     * @return array{day_projection: DayProjection, affiliate_results: array, per_affiliate: array}
     */
    public function projectDay(
        Collection $network,
        Collection $todayTransactions,
        Collection $allTransactions,
        PlanConfig $planConfig,
        string $dateString,
        int $day,
        string $rollingViralPaid = '0',
        string $rollingAllPaid = '0',
    ): array {
        $date = Carbon::parse($dateString);
        $windowStart = $date->copy()->subDays($planConfig->rolling_days - 1);

        // Filter to confirmed, qualifying transactions in the rolling window
        $windowTransactions = $allTransactions->filter(function (array $txn) use ($windowStart, $dateString) {
            return $txn['status'] === 'confirmed'
                && $txn['qualifies_for_commission']
                && $txn['transaction_date'] >= $windowStart->toDateString()
                && $txn['transaction_date'] <= $dateString;
        });

        // Today's confirmed qualifying transactions
        $todayConfirmed = $todayTransactions->filter(function (array $txn) {
            return $txn['status'] === 'confirmed' && $txn['qualifies_for_commission'];
        });

        // Calculate rolling 30d company volume
        $rolling30dVolume = $windowTransactions->reduce(
            fn (string $carry, array $txn) => bcadd($carry, $txn['xp'], 4),
            '0'
        );

        // Daily volume
        $dailyVolume = $todayConfirmed->reduce(
            fn (string $carry, array $txn) => bcadd($carry, $txn['xp'], 4),
            '0'
        );

        // Active affiliates
        $activeAffiliates = $network->where('role', 'affiliate')->where('status', 'active');

        // Build sponsor → subtree mapping for leg volume computation
        $sponsorTree = $this->buildSponsorTree($network);

        $rawResults = [];
        $perAffiliate = [];

        foreach ($activeAffiliates as $affiliate) {
            $affiliateId = $affiliate['id'];

            // Compute qualification from synthetic data
            $activeCustomerCount = $this->countActiveCustomers(
                $affiliateId, $windowTransactions, $planConfig
            );
            $referredVolume = $this->sumReferredVolume($affiliateId, $windowTransactions);

            // Match affiliate tier (same logic as QualificationEvaluator::matchAffiliateTier)
            $affiliateTierIndex = $this->matchAffiliateTier($activeCustomerCount, $referredVolume, $planConfig);
            $affiliateTierRate = $affiliateTierIndex !== null
                ? $planConfig->affiliate_tiers[$affiliateTierIndex]->rate
                : null;

            // Compute affiliate (direct) commission on today's new volume
            $affiliateCommission = '0';
            if ($affiliateTierIndex !== null) {
                $todayReferredVolume = $todayConfirmed
                    ->where('referred_by_user_id', $affiliateId)
                    ->reduce(fn (string $c, array $t) => bcadd($c, $t['xp'], 4), '0');

                $affiliateCommission = bcmul($todayReferredVolume, (string) $affiliateTierRate, 4);
            }

            // Compute leg volumes from synthetic tree
            $legVolumes = $this->computeLegVolumes($affiliateId, $sponsorTree, $windowTransactions);

            // Reuse Phase 1 QvvCalculator (pure math, no DB)
            $volumeSnapshot = $this->qvvCalculator->calculate($legVolumes, $planConfig);

            // Reuse Phase 1 ViralCommissionCalculator (pure math, no DB)
            $viralResult = $this->viralCalculator->calculate(
                $activeCustomerCount,
                $referredVolume,
                $volumeSnapshot,
                $planConfig,
            );

            $rawResults[] = [
                'user_id' => $affiliateId,
                'affiliate_commission' => $affiliateCommission,
                'affiliate_tier_index' => $affiliateTierIndex,
                'affiliate_tier_rate' => $affiliateTierRate,
                'viral_commission' => $viralResult['amount'],
                'viral_tier' => $viralResult['tier'],
            ];

            $perAffiliate[$affiliateId] = [
                'affiliate_tier_index' => $affiliateTierIndex,
                'affiliate_tier_rate' => $affiliateTierRate,
                'viral_tier' => $viralResult['tier'],
            ];
        }

        // Apply caps (in-memory version of CapEnforcer logic)
        $capResult = $this->enforceCapsSynthetic(
            $rawResults, $rolling30dVolume, $rollingViralPaid, $rollingAllPaid, $planConfig
        );

        // Sum totals from adjusted results
        $totalAffiliate = '0';
        $totalViral = '0';
        foreach ($capResult['adjusted_results'] as $result) {
            $totalAffiliate = bcadd($totalAffiliate, $result['affiliate_commission'], 4);
            $totalViral = bcadd($totalViral, $result['viral_commission'], 4);
        }
        $totalPayout = bcadd($totalAffiliate, $totalViral, 4);

        // Payout ratio
        $payoutRatio = bccomp($rolling30dVolume, '0', 4) > 0
            ? bcmul(bcdiv(bcadd($rollingAllPaid, $totalPayout, 4), $rolling30dVolume, 6), '100', 2)
            : '0.00';

        // Count active customers (unique customers with qualifying orders in window)
        $activeCustomerIds = $windowTransactions->pluck('user_id')->unique()
            ->intersect($network->where('role', 'customer')->where('status', 'active')->pluck('id'));

        $dayProjection = new DayProjection(
            day: $day,
            date: $dateString,
            total_affiliates: $network->where('role', 'affiliate')->where('status', 'active')->count(),
            total_customers: $network->where('role', 'customer')->where('status', 'active')->count(),
            active_customers: $activeCustomerIds->count(),
            daily_volume: $dailyVolume,
            rolling_30d_volume: $rolling30dVolume,
            affiliate_commissions: $totalAffiliate,
            viral_commissions: $totalViral,
            total_payout: $totalPayout,
            payout_ratio_percent: $payoutRatio,
            viral_cap_applied: $capResult['viral_cap_triggered'],
            global_cap_applied: $capResult['global_cap_triggered'],
        );

        return [
            'day_projection' => $dayProjection,
            'adjusted_results' => $capResult['adjusted_results'],
            'per_affiliate' => $perAffiliate,
        ];
    }

    /**
     * Count active customers for an affiliate from synthetic transactions.
     * Mirrors QualificationEvaluator::countActiveCustomers logic.
     */
    private function countActiveCustomers(int $affiliateId, Collection $windowTransactions, PlanConfig $config): int
    {
        $referred = $windowTransactions->where('referred_by_user_id', $affiliateId);

        if ($config->active_customer_threshold_type === 'per_order') {
            return $referred
                ->filter(fn (array $t) => (float) $t['xp'] >= $config->active_customer_min_order_xp)
                ->pluck('user_id')
                ->unique()
                ->count();
        }

        // cumulative_in_window
        return $referred
            ->groupBy('user_id')
            ->filter(fn (Collection $group) => $group->sum('xp') >= $config->active_customer_min_order_xp)
            ->count();
    }

    /**
     * Sum referred volume for an affiliate from synthetic transactions.
     * Mirrors QualificationEvaluator::sumReferredVolume logic.
     */
    private function sumReferredVolume(int $affiliateId, Collection $windowTransactions): string
    {
        return $windowTransactions
            ->where('referred_by_user_id', $affiliateId)
            ->reduce(fn (string $c, array $t) => bcadd($c, $t['xp'], 4), '0');
    }

    /**
     * Match affiliate tier. Same logic as QualificationEvaluator::matchAffiliateTier.
     */
    private function matchAffiliateTier(int $customerCount, string $referredVolume, PlanConfig $config): ?int
    {
        $matchedIndex = null;
        foreach ($config->affiliate_tiers as $index => $tier) {
            if ($customerCount >= $tier->min_active_customers
                && bccomp($referredVolume, (string) $tier->min_referred_volume, 4) >= 0) {
                $matchedIndex = $index;
            }
        }
        return $matchedIndex;
    }

    /**
     * Build a sponsor → direct children map from synthetic network.
     */
    private function buildSponsorTree(Collection $network): array
    {
        $tree = [];
        foreach ($network as $user) {
            if ($user['sponsor_id'] !== null) {
                $tree[$user['sponsor_id']][] = $user['id'];
            }
        }
        return $tree;
    }

    /**
     * Compute leg volumes from synthetic tree data.
     * Mirrors LegAggregator::getLegVolumes logic but on in-memory data.
     */
    private function computeLegVolumes(int $affiliateId, array $sponsorTree, Collection $windowTransactions): array
    {
        $directChildren = $sponsorTree[$affiliateId] ?? [];
        if (empty($directChildren)) {
            return [];
        }

        $legs = [];
        foreach ($directChildren as $childId) {
            $subtreeIds = $this->getSubtreeIds($childId, $sponsorTree);
            $volume = $windowTransactions
                ->whereIn('user_id', $subtreeIds)
                ->reduce(fn (string $c, array $t) => bcadd($c, $t['xp'], 4), '0');

            $legs[] = [
                'leg_root_user_id' => $childId,
                'volume' => $volume,
            ];
        }

        return $legs;
    }

    /**
     * Get all descendant IDs (inclusive) via BFS.
     */
    private function getSubtreeIds(int $rootId, array $sponsorTree): array
    {
        $ids = [$rootId];
        $queue = [$rootId];

        while (!empty($queue)) {
            $current = array_shift($queue);
            $children = $sponsorTree[$current] ?? [];
            foreach ($children as $childId) {
                $ids[] = $childId;
                $queue[] = $childId;
            }
        }

        return $ids;
    }

    /**
     * In-memory cap enforcement. Same algorithm as CapEnforcer::enforce
     * but operates on synthetic rolling totals instead of DB queries.
     */
    private function enforceCapsSynthetic(
        array $commissionResults,
        string $rolling30dVolume,
        string $rollingViralPaid,
        string $rollingAllPaid,
        PlanConfig $config,
    ): array {
        $todayViralTotal = '0';
        $todayAffiliateTotal = '0';
        foreach ($commissionResults as $result) {
            $todayViralTotal = bcadd($todayViralTotal, $result['viral_commission'], 4);
            $todayAffiliateTotal = bcadd($todayAffiliateTotal, $result['affiliate_commission'], 4);
        }

        $viralReductionPct = '0';
        $viralCapTriggered = false;

        // Viral cap
        if ($config->viral_cap_enforcement === 'daily_reduction' && bccomp($rolling30dVolume, '0', 4) > 0) {
            $totalViralWithToday = bcadd($rollingViralPaid, $todayViralTotal, 4);
            $viralPct = bcdiv($totalViralWithToday, $rolling30dVolume, 8);

            if (bccomp($viralPct, (string) $config->viral_cap_percent, 8) > 0) {
                $viralReductionPct = bcsub($viralPct, (string) $config->viral_cap_percent, 8);
                $viralCapTriggered = true;
            }
        }

        $adjustedResults = $commissionResults;
        if ($viralCapTriggered) {
            $reductionMultiplier = bcsub('1', $viralReductionPct, 8);
            if (bccomp($reductionMultiplier, '0', 8) < 0) {
                $reductionMultiplier = '0';
            }
            foreach ($adjustedResults as &$result) {
                $result['viral_commission'] = bcmul($result['viral_commission'], $reductionMultiplier, 4);
            }
            unset($result);

            $todayViralTotal = '0';
            foreach ($adjustedResults as $result) {
                $todayViralTotal = bcadd($todayViralTotal, $result['viral_commission'], 4);
            }
        }

        // Global cap
        $globalCapTriggered = false;
        if ($config->total_payout_cap_enforcement === 'proportional_reduction' && bccomp($rolling30dVolume, '0', 4) > 0) {
            $todayTotal = bcadd($todayAffiliateTotal, $todayViralTotal, 4);
            $totalAllWithToday = bcadd($rollingAllPaid, $todayTotal, 4);
            $totalPct = bcdiv($totalAllWithToday, $rolling30dVolume, 8);

            if (bccomp($totalPct, (string) $config->total_payout_cap_percent, 8) > 0) {
                $globalReductionPct = bcsub($totalPct, (string) $config->total_payout_cap_percent, 8);
                $globalCapTriggered = true;

                $reductionMultiplier = bcsub('1', $globalReductionPct, 8);
                if (bccomp($reductionMultiplier, '0', 8) < 0) {
                    $reductionMultiplier = '0';
                }
                foreach ($adjustedResults as &$result) {
                    $result['affiliate_commission'] = bcmul($result['affiliate_commission'], $reductionMultiplier, 4);
                    $result['viral_commission'] = bcmul($result['viral_commission'], $reductionMultiplier, 4);
                }
                unset($result);
            }
        }

        return [
            'viral_cap_triggered' => $viralCapTriggered,
            'global_cap_triggered' => $globalCapTriggered,
            'adjusted_results' => $adjustedResults,
        ];
    }
}
