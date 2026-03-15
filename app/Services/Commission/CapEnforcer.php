<?php

namespace App\Services\Commission;

use App\DTOs\PlanConfig;
use App\Models\CommissionLedgerEntry;
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

    private function getRollingCompanyVolume(int $companyId, Carbon $windowStart, Carbon $windowEnd): string
    {
        $volume = Transaction::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('status', 'confirmed')
            ->where('qualifies_for_commission', true)
            ->whereBetween('transaction_date', [$windowStart->toDateString(), $windowEnd->toDateString()])
            ->sum('xp');

        return (string) $volume;
    }

    private function getRollingViralCommissions(int $companyId, Carbon $windowStart, Carbon $windowEnd): string
    {
        $total = CommissionLedgerEntry::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('type', 'viral_commission')
            ->whereHas('commissionRun', function ($query) use ($windowStart, $windowEnd) {
                $query->whereBetween('run_date', [$windowStart->toDateString(), $windowEnd->toDateString()]);
            })
            ->sum('amount');

        return (string) $total;
    }

    private function getRollingAllCommissions(int $companyId, Carbon $windowStart, Carbon $windowEnd): string
    {
        $total = CommissionLedgerEntry::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereIn('type', ['affiliate_commission', 'viral_commission'])
            ->whereHas('commissionRun', function ($query) use ($windowStart, $windowEnd) {
                $query->whereBetween('run_date', [$windowStart->toDateString(), $windowEnd->toDateString()]);
            })
            ->sum('amount');

        return (string) $total;
    }
}
