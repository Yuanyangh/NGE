<?php

namespace App\Services\Commission;

use App\DTOs\PlanConfig;
use App\Models\CommissionLedgerEntry;
use App\Scopes\CompanyScope;
use App\Models\Transaction;
use Carbon\Carbon;

class CapEnforcer
{
    /**
     * Enforce viral and global caps.
     *
     * Returns ['viral_reduction_pct' => string, 'global_reduction_pct' => string,
     *          'viral_cap_triggered' => bool, 'global_cap_triggered' => bool,
     *          'adjusted_results' => array]
     *
     * @param array $commissionResults Array of CommissionResult-like arrays with user_id, affiliate_commission, viral_commission
     */
    public function enforce(
        array $commissionResults,
        int $companyId,
        Carbon $date,
        PlanConfig $config,
    ): array {
        $windowStart = $date->copy()->subDays($config->rolling_days - 1);

        // Get rolling 30-day company volume
        $rolling30dVolume = $this->getRollingCompanyVolume($companyId, $windowStart, $date);

        // Get rolling 30-day commissions already paid (before today)
        $rolling30dViralPaid = $this->getRollingViralCommissions($companyId, $windowStart, $date->copy()->subDay());
        $rolling30dAllPaid = $this->getRollingAllCommissions($companyId, $windowStart, $date->copy()->subDay());

        // Sum today's proposed commissions
        $todayViralTotal = '0';
        $todayAffiliateTotal = '0';
        foreach ($commissionResults as $result) {
            $todayViralTotal = bcadd($todayViralTotal, $result['viral_commission'], 4);
            $todayAffiliateTotal = bcadd($todayAffiliateTotal, $result['affiliate_commission'], 4);
        }

        $viralReductionPct = '0';
        $viralCapTriggered = false;

        // === Step 1: Viral Cap (15% of company volume) ===
        if ($config->viral_cap_enforcement === 'daily_reduction' && bccomp($rolling30dVolume, '0', 4) > 0) {
            $totalViralWithToday = bcadd($rolling30dViralPaid, $todayViralTotal, 4);
            $viralPct = bcdiv($totalViralWithToday, $rolling30dVolume, 8);

            if (bccomp($viralPct, (string) $config->viral_cap_percent, 8) > 0) {
                $viralReductionPct = bcsub($viralPct, (string) $config->viral_cap_percent, 8);
                $viralCapTriggered = true;
            }
        }

        // Apply viral reduction to today's viral commissions
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

            // Recalculate today's viral total after reduction
            $todayViralTotal = '0';
            foreach ($adjustedResults as $result) {
                $todayViralTotal = bcadd($todayViralTotal, $result['viral_commission'], 4);
            }
        }

        // === Step 2: Global Cap (35% of company volume) ===
        $globalReductionPct = '0';
        $globalCapTriggered = false;

        if ($config->total_payout_cap_enforcement === 'proportional_reduction' && bccomp($rolling30dVolume, '0', 4) > 0) {
            $todayTotalCommissions = bcadd($todayAffiliateTotal, $todayViralTotal, 4);
            $totalAllWithToday = bcadd($rolling30dAllPaid, $todayTotalCommissions, 4);
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
            'viral_reduction_pct' => $viralReductionPct,
            'global_reduction_pct' => $globalReductionPct,
            'viral_cap_triggered' => $viralCapTriggered,
            'global_cap_triggered' => $globalCapTriggered,
            'rolling_30d_volume' => $rolling30dVolume,
            'adjusted_results' => $adjustedResults,
        ];
    }

    /**
     * Re-check global cap including bonus amounts.
     *
     * After bonuses are calculated, the total payout may exceed the global cap.
     * This method checks rolling commissions (already paid, including today's run
     * if already written) plus today's proposed bonuses against the cap.
     * Only bonus amounts are reduced if the cap is exceeded -- commissions have
     * already been capped by the main enforce() method.
     *
     * @param  array  $adjustedResults  Commission results (already cap-adjusted, for reference)
     * @param  Collection  $bonusResults  Collection of BonusResult objects
     * @param  int  $companyId
     * @param  Carbon  $date
     * @param  PlanConfig  $config
     * @return array{adjusted_results: array, adjusted_bonuses: Collection, global_cap_with_bonus_triggered: bool, global_bonus_reduction_pct: string}
     */
    public function enforceGlobalCapWithBonuses(
        array $adjustedResults,
        \Illuminate\Support\Collection $bonusResults,
        int $companyId,
        Carbon $date,
        PlanConfig $config,
    ): array {
        $windowStart = $date->copy()->subDays($config->rolling_days - 1);
        $rolling30dVolume = $this->getRollingCompanyVolume($companyId, $windowStart, $date);

        if (bccomp($rolling30dVolume, '0', 4) <= 0) {
            return [
                'adjusted_results' => $adjustedResults,
                'adjusted_bonuses' => $bonusResults,
                'global_cap_with_bonus_triggered' => false,
                'global_bonus_reduction_pct' => '0',
            ];
        }

        // Get all rolling 30-day commissions already paid (including today if written)
        $rolling30dAllPaid = $this->getRollingAllCommissions($companyId, $windowStart, $date);

        // Also include rolling 30-day bonus ledger entries already paid (before today)
        $rolling30dBonusPaid = $this->getRollingBonusPayments($companyId, $windowStart, $date->copy()->subDay());

        // Sum today's proposed bonuses
        $todayBonusTotal = '0';
        foreach ($bonusResults as $bonus) {
            $todayBonusTotal = bcadd($todayBonusTotal, $bonus->amount, 4);
        }

        // Total = historical commissions + historical bonuses + today's proposed bonuses
        $historicalTotal = bcadd($rolling30dAllPaid, $rolling30dBonusPaid, 4);
        $grandTotal = bcadd($historicalTotal, $todayBonusTotal, 4);
        $totalPct = bcdiv($grandTotal, $rolling30dVolume, 8);

        if (bccomp($totalPct, (string) $config->total_payout_cap_percent, 8) <= 0) {
            return [
                'adjusted_results' => $adjustedResults,
                'adjusted_bonuses' => $bonusResults,
                'global_cap_with_bonus_triggered' => false,
                'global_bonus_reduction_pct' => '0',
            ];
        }

        $overagePct = bcsub($totalPct, (string) $config->total_payout_cap_percent, 8);
        $reductionMultiplier = bcsub('1', $overagePct, 8);
        if (bccomp($reductionMultiplier, '0', 8) < 0) {
            $reductionMultiplier = '0';
        }

        // Only reduce bonuses -- commissions were already capped
        $adjustedBonuses = $bonusResults->map(function ($bonus) use ($reductionMultiplier) {
            return new \App\DTOs\BonusResult(
                user_id: $bonus->user_id,
                amount: bcmul($bonus->amount, $reductionMultiplier, 4),
                bonus_type_id: $bonus->bonus_type_id,
                tier_achieved: $bonus->tier_achieved,
                qualification_snapshot: $bonus->qualification_snapshot,
                description: $bonus->description,
            );
        });

        return [
            'adjusted_results' => $adjustedResults,
            'adjusted_bonuses' => $adjustedBonuses,
            'global_cap_with_bonus_triggered' => true,
            'global_bonus_reduction_pct' => $overagePct,
        ];
    }

    private function getRollingBonusPayments(int $companyId, Carbon $windowStart, Carbon $windowEnd): string
    {
        $total = \App\Models\BonusLedgerEntry::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $companyId)
            ->whereHas('commissionRun', function ($query) use ($windowStart, $windowEnd) {
                $query->whereDate('run_date', '>=', $windowStart->toDateString())
                    ->whereDate('run_date', '<=', $windowEnd->toDateString());
            })
            ->sum('amount');

        return (string) $total;
    }

    private function getRollingCompanyVolume(int $companyId, Carbon $windowStart, Carbon $windowEnd): string
    {
        $volume = Transaction::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $companyId)
            ->where('status', 'confirmed')
            ->where('qualifies_for_commission', true)
            ->whereDate('transaction_date', '>=', $windowStart->toDateString())
            ->whereDate('transaction_date', '<=', $windowEnd->toDateString())
            ->sum('xp');

        return (string) $volume;
    }

    private function getRollingViralCommissions(int $companyId, Carbon $windowStart, Carbon $windowEnd): string
    {
        $total = CommissionLedgerEntry::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $companyId)
            ->where('type', 'viral_commission')
            ->whereHas('commissionRun', function ($query) use ($windowStart, $windowEnd) {
                $query->whereDate('run_date', '>=', $windowStart->toDateString())
                    ->whereDate('run_date', '<=', $windowEnd->toDateString());
            })
            ->sum('amount');

        return (string) $total;
    }

    private function getRollingAllCommissions(int $companyId, Carbon $windowStart, Carbon $windowEnd): string
    {
        $total = CommissionLedgerEntry::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $companyId)
            ->whereIn('type', ['affiliate_commission', 'viral_commission'])
            ->whereHas('commissionRun', function ($query) use ($windowStart, $windowEnd) {
                $query->whereDate('run_date', '>=', $windowStart->toDateString())
                    ->whereDate('run_date', '<=', $windowEnd->toDateString());
            })
            ->sum('amount');

        return (string) $total;
    }
}
