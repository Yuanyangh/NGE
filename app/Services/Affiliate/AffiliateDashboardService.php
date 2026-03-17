<?php

namespace App\Services\Affiliate;

use App\DTOs\AffiliateDashboardData;
use App\DTOs\PlanConfig;
use App\Models\CommissionLedgerEntry;
use App\Models\User;
use App\Scopes\CompanyScope;
use App\Models\WalletMovement;
use App\Services\Commission\QualificationEvaluator;
use Carbon\Carbon;

class AffiliateDashboardService
{
    public function __construct(
        private QualificationEvaluator $qualificationEvaluator,
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

        return new AffiliateDashboardData(
            total_earned_30d: $totalEarned30d,
            pending_amount: $pendingAmount,
            wallet_balance: $walletBalance,
            current_affiliate_rate: $qualification->affiliate_tier_rate,
            current_viral_tier: $qualification->viral_tier,
            current_viral_daily_reward: $qualification->viral_daily_reward,
            recent_activity: $recentEntries,
        );
    }
}
