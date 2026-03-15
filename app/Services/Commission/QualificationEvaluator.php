<?php

namespace App\Services\Commission;

use App\DTOs\PlanConfig;
use App\DTOs\QualificationResult;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class QualificationEvaluator
{
    public function evaluate(User $affiliate, Carbon $date, PlanConfig $config): QualificationResult
    {
        $windowStart = $date->copy()->subDays($config->rolling_days - 1);

        $activeCustomerCount = $this->countActiveCustomers($affiliate, $windowStart, $date, $config);
        $referredVolume = $this->sumReferredVolume($affiliate, $windowStart, $date);

        $affiliateTierIndex = $this->matchAffiliateTier($activeCustomerCount, $referredVolume, $config);
        $viralTier = $this->matchViralTier($activeCustomerCount, $referredVolume, $config);

        $reasons = [];
        $isQualified = false;

        if ($activeCustomerCount === 0) {
            $reasons[] = 'No active customers in rolling window';
        }

        if ($affiliateTierIndex !== null) {
            $tier = $config->affiliate_tiers[$affiliateTierIndex];
            $reasons[] = sprintf(
                'Affiliate tier %d: %d customers, %.2f volume → %.0f%% rate',
                $affiliateTierIndex + 1,
                $activeCustomerCount,
                (float) $referredVolume,
                $tier->rate * 100
            );
            $isQualified = true;
        } else {
            $reasons[] = 'No affiliate tier matched';
        }

        if ($viralTier !== null) {
            $vt = $config->viral_tiers[$viralTier];
            $reasons[] = sprintf(
                'Viral tier %d qualified (requires %d customers, %.0f volume)',
                $vt->tier,
                $vt->min_active_customers,
                $vt->min_referred_volume
            );
            $isQualified = true;
        }

        return new QualificationResult(
            is_qualified: $isQualified,
            active_customer_count: $activeCustomerCount,
            referred_volume_30d: $referredVolume,
            affiliate_tier_index: $affiliateTierIndex,
            affiliate_tier_rate: $affiliateTierIndex !== null ? $config->affiliate_tiers[$affiliateTierIndex]->rate : null,
            viral_tier: $viralTier !== null ? $config->viral_tiers[$viralTier]->tier : null,
            viral_daily_reward: $viralTier !== null ? $config->viral_tiers[$viralTier]->daily_reward : null,
            reasons: $reasons,
        );
    }

    public function countActiveCustomers(User $affiliate, Carbon $windowStart, Carbon $windowEnd, PlanConfig $config): int
    {
        $minXp = $config->active_customer_min_order_xp;

        if ($config->active_customer_threshold_type === 'per_order') {
            return Transaction::withoutGlobalScopes()
                ->where('referred_by_user_id', $affiliate->id)
                ->where('company_id', $affiliate->company_id)
                ->where('status', 'confirmed')
                ->where('qualifies_for_commission', true)
                ->whereDate('transaction_date', '>=', $windowStart->toDateString())
                ->whereDate('transaction_date', '<=', $windowEnd->toDateString())
                ->where('xp', '>=', $minXp)
                ->distinct('user_id')
                ->count('user_id');
        }

        // cumulative_in_window: sum XP per user, count those >= threshold
        return Transaction::withoutGlobalScopes()
            ->where('referred_by_user_id', $affiliate->id)
            ->where('company_id', $affiliate->company_id)
            ->where('status', 'confirmed')
            ->where('qualifies_for_commission', true)
            ->whereDate('transaction_date', '>=', $windowStart->toDateString())
            ->whereDate('transaction_date', '<=', $windowEnd->toDateString())
            ->groupBy('user_id')
            ->havingRaw('SUM(xp) >= ?', [$minXp])
            ->select('user_id', DB::raw('SUM(xp) as total_xp'))
            ->get()
            ->count();
    }

    public function sumReferredVolume(User $affiliate, Carbon $windowStart, Carbon $windowEnd): string
    {
        $volume = Transaction::withoutGlobalScopes()
            ->where('referred_by_user_id', $affiliate->id)
            ->where('company_id', $affiliate->company_id)
            ->where('status', 'confirmed')
            ->where('qualifies_for_commission', true)
            ->whereDate('transaction_date', '>=', $windowStart->toDateString())
            ->whereDate('transaction_date', '<=', $windowEnd->toDateString())
            ->sum('xp');

        return (string) $volume;
    }

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

    private function matchViralTier(int $customerCount, string $referredVolume, PlanConfig $config, ?string $qvv = null): ?int
    {
        $matchedIndex = null;

        foreach ($config->viral_tiers as $index => $tier) {
            if ($customerCount >= $tier->min_active_customers
                && bccomp($referredVolume, (string) $tier->min_referred_volume, 4) >= 0) {
                // QVV check is done separately in ViralCommissionCalculator
                // Here we just check customer + volume qualification for viral eligibility
                if ($qvv === null || bccomp($qvv, (string) $tier->min_qvv, 4) >= 0) {
                    $matchedIndex = $index;
                }
            }
        }

        return $matchedIndex;
    }

    public function matchViralTierWithQvv(int $customerCount, string $referredVolume, string $qvv, PlanConfig $config): ?int
    {
        return $this->matchViralTier($customerCount, $referredVolume, $config, $qvv);
    }
}
