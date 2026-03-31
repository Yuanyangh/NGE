<?php

namespace App\Services\Affiliate;

use App\DTOs\AffiliateDashboardData;
use App\DTOs\PlanConfig;
use App\Models\CommissionLedgerEntry;
use App\Models\User;
use App\Scopes\CompanyScope;
use App\Models\WalletMovement;
use App\Services\Commission\LegAggregator;
use App\Services\Commission\QualificationEvaluator;
use App\Services\Commission\QvvCalculator;
use Carbon\Carbon;

class AffiliateDashboardService
{
    public function __construct(
        private QualificationEvaluator $qualificationEvaluator,
        private LegAggregator $legAggregator,
        private QvvCalculator $qvvCalculator,
    ) {}

    public function getDashboardData(User $affiliate, Carbon $date, PlanConfig $config): AffiliateDashboardData
    {
        $windowStart = $date->copy()->subDays(29);

        // Total earned in 30d
        $totalEarned30d = (string) CommissionLedgerEntry::withoutGlobalScope(CompanyScope::class)
            ->where('user_id', $affiliate->id)
            ->where('company_id', $affiliate->company_id)
            ->whereDate('created_at', '>=', $windowStart->toDateString())
            ->whereDate('created_at', '<=', $date->toDateString())
            ->whereIn('type', ['affiliate_commission', 'viral_commission'])
            ->sum('amount');

        // Wallet balances
        $walletAccount = $affiliate->walletAccount;
        $pendingAmount = '0';
        $walletBalance = '0';

        if ($walletAccount) {
            $pendingAmount = (string) WalletMovement::withoutGlobalScope(CompanyScope::class)
                ->where('wallet_account_id', $walletAccount->id)
                ->where('company_id', $affiliate->company_id)
                ->where('status', 'pending')
                ->sum('amount');

            $walletBalance = (string) WalletMovement::withoutGlobalScope(CompanyScope::class)
                ->where('wallet_account_id', $walletAccount->id)
                ->where('company_id', $affiliate->company_id)
                ->whereIn('status', ['approved', 'released'])
                ->sum('amount');
        }

        // Qualification
        $qualification = $this->qualificationEvaluator->evaluate($affiliate, $date, $config);

        // Recent activity
        $recentEntries = CommissionLedgerEntry::withoutGlobalScope(CompanyScope::class)
            ->where('user_id', $affiliate->id)
            ->where('company_id', $affiliate->company_id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn (CommissionLedgerEntry $entry) => [
                'id' => $entry->id,
                'type' => $entry->type,
                'amount' => $entry->amount,
                'tier_achieved' => $entry->tier_achieved,
                'description' => $entry->description,
                'created_at' => $entry->created_at?->toDateTimeString(),
            ])
            ->toArray();

        // Compute effective viral tier using actual QVV
        $legVolumes = $this->legAggregator->getLegVolumes($affiliate, $date, $config);
        $volumeSnapshot = $this->qvvCalculator->calculate($legVolumes, $config);
        $currentQvv = $volumeSnapshot->qualifying_viral_volume;

        $effectiveViralTier = $qualification->viral_tier;
        $effectiveViralDailyReward = $qualification->viral_daily_reward;

        $viralTierIndex = $this->qualificationEvaluator->matchViralTierWithQvv(
            $qualification->active_customer_count,
            $qualification->referred_volume_30d,
            $currentQvv,
            $config
        );

        if ($viralTierIndex !== null) {
            $effectiveViralTier = $config->viral_tiers[$viralTierIndex]->tier;
            $effectiveViralDailyReward = $config->viral_tiers[$viralTierIndex]->daily_reward;
        } else {
            $effectiveViralTier = null;
            $effectiveViralDailyReward = null;
        }

        return new AffiliateDashboardData(
            total_earned_30d: $totalEarned30d,
            pending_amount: $pendingAmount,
            wallet_balance: $walletBalance,
            current_affiliate_tier: $qualification->affiliate_tier_index !== null ? $qualification->affiliate_tier_index + 1 : null,
            current_affiliate_rate: $qualification->affiliate_tier_rate,
            current_viral_tier: $effectiveViralTier,
            current_viral_daily_reward: $effectiveViralDailyReward,
            recent_activity: $recentEntries,
        );
    }
}
