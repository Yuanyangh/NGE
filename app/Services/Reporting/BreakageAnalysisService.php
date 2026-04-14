<?php

namespace App\Services\Reporting;

use App\DTOs\BreakageData;
use App\DTOs\PlanConfig;
use App\Models\CompensationPlan;
use App\Scopes\CompanyScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BreakageAnalysisService
{
    public function analyze(int $companyId, Carbon $startDate, Carbon $endDate): BreakageData
    {
        $startStr = $startDate->toDateString();
        $endStr = $endDate->toDateString();

        $xpThreshold = $this->getXpThreshold($companyId);

        // --- Wasted volume: transactions where xp < threshold ---
        $wastedStats = DB::table('transactions')
            ->where('company_id', $companyId)
            ->whereBetween('transaction_date', [$startStr, $endStr])
            ->where('status', 'confirmed')
            ->where('type', '!=', 'refund')
            ->where('xp', '<', $xpThreshold)
            ->selectRaw('COALESCE(SUM(xp), 0) as wasted_xp')
            ->selectRaw('COUNT(*) as wasted_count')
            ->first();

        $wastedVolumeXp = (string) ($wastedStats->wasted_xp ?? '0');
        $wastedTransactionCount = (int) ($wastedStats->wasted_count ?? 0);

        // --- Qualifying volume ---
        $qualifyingXp = DB::table('transactions')
            ->where('company_id', $companyId)
            ->whereBetween('transaction_date', [$startStr, $endStr])
            ->where('status', 'confirmed')
            ->where('type', '!=', 'refund')
            ->where('xp', '>=', $xpThreshold)
            ->selectRaw('COALESCE(SUM(xp), 0) as qualifying_xp')
            ->value('qualifying_xp');

        $qualifyingVolumeXp = (string) ($qualifyingXp ?? '0');

        $totalXp = bcadd($wastedVolumeXp, $qualifyingVolumeXp, 4);
        $wastedPercentage = bccomp($totalXp, '0', 4) > 0
            ? bcmul(bcdiv($wastedVolumeXp, $totalXp, 6), '100', 2)
            : '0.00';

        // --- Cap reductions ---
        // Use JSON_EXTRACT only (works in both MySQL and SQLite)
        // MySQL JSON_EXTRACT returns quoted strings ("viral"), SQLite returns unquoted (viral)
        // So we check for both forms
        $capReductions = DB::table('commission_ledger_entries')
            ->join('commission_runs', 'commission_runs.id', '=', 'commission_ledger_entries.commission_run_id')
            ->where('commission_ledger_entries.company_id', $companyId)
            ->whereBetween('commission_runs.run_date', [$startStr, $endStr])
            ->where('commission_ledger_entries.type', 'cap_adjustment')
            ->select(
                DB::raw("COALESCE(SUM(ABS(commission_ledger_entries.amount)), 0) as total_reduction"),
                DB::raw("SUM(CASE WHEN JSON_EXTRACT(commission_ledger_entries.qualification_snapshot, '$.cap_type') IN ('viral', '\"viral\"') THEN ABS(commission_ledger_entries.amount) ELSE 0 END) as viral_reduction"),
                DB::raw("SUM(CASE WHEN JSON_EXTRACT(commission_ledger_entries.qualification_snapshot, '$.cap_type') IN ('global', '\"global\"') THEN ABS(commission_ledger_entries.amount) ELSE 0 END) as global_reduction"),
                DB::raw("SUM(CASE WHEN JSON_EXTRACT(commission_ledger_entries.qualification_snapshot, '$.cap_type') IN ('viral', '\"viral\"') THEN 1 ELSE 0 END) as viral_trigger_count"),
                DB::raw("SUM(CASE WHEN JSON_EXTRACT(commission_ledger_entries.qualification_snapshot, '$.cap_type') IN ('global', '\"global\"') THEN 1 ELSE 0 END) as global_trigger_count")
            )
            ->first();

        $viralCapReduction = (string) ($capReductions->viral_reduction ?? '0');
        $globalCapReduction = (string) ($capReductions->global_reduction ?? '0');
        $totalCapReduction = (string) ($capReductions->total_reduction ?? '0');
        $viralCapTriggerCount = (int) ($capReductions->viral_trigger_count ?? 0);
        $globalCapTriggerCount = (int) ($capReductions->global_trigger_count ?? 0);

        // --- Clawbacks ---
        $clawbackStats = DB::table('wallet_movements')
            ->where('company_id', $companyId)
            ->where('type', 'clawback')
            ->whereBetween('created_at', [
                $startDate->copy()->startOfDay()->toDateTimeString(),
                $endDate->copy()->endOfDay()->toDateTimeString(),
            ])
            ->selectRaw('COALESCE(SUM(ABS(amount)), 0) as clawback_total')
            ->selectRaw('COUNT(*) as clawback_count')
            ->first();

        $clawbackTotal = (string) ($clawbackStats->clawback_total ?? '0');
        $clawbackCount = (int) ($clawbackStats->clawback_count ?? 0);

        // --- Breakage rate ---
        $totalCommissions = DB::table('commission_runs')
            ->where('company_id', $companyId)
            ->whereBetween('run_date', [$startStr, $endStr])
            ->where('status', 'completed')
            ->selectRaw('COALESCE(SUM(total_affiliate_commission), 0) + COALESCE(SUM(total_viral_commission), 0) as total_comm')
            ->value('total_comm');

        $totalComm = (string) ($totalCommissions ?? '0');
        $denominator = bcadd($totalComm, $totalCapReduction, 4);
        $breakageRate = bccomp($denominator, '0', 4) > 0
            ? bcmul(bcdiv($totalCapReduction, $denominator, 6), '100', 2)
            : '0.00';

        return new BreakageData(
            wastedVolumeXp: bcadd($wastedVolumeXp, '0', 2),
            qualifyingVolumeXp: bcadd($qualifyingVolumeXp, '0', 2),
            wastedPercentage: $wastedPercentage,
            wastedTransactionCount: $wastedTransactionCount,
            xpThreshold: $xpThreshold,
            viralCapReduction: bcadd($viralCapReduction, '0', 2),
            globalCapReduction: bcadd($globalCapReduction, '0', 2),
            totalCapReduction: bcadd($totalCapReduction, '0', 2),
            viralCapTriggerCount: $viralCapTriggerCount,
            globalCapTriggerCount: $globalCapTriggerCount,
            clawbackTotal: bcadd($clawbackTotal, '0', 2),
            clawbackCount: $clawbackCount,
            breakageRate: $breakageRate,
            periodStart: $startDate->toDateString(),
            periodEnd: $endDate->toDateString(),
        );
    }

    private function getXpThreshold(int $companyId): int
    {
        $plan = CompensationPlan::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        if ($plan === null || !is_array($plan->config)) {
            return 20;
        }

        $config = PlanConfig::fromArray($plan->config);

        return (int) $config->active_customer_min_order_xp;
    }
}
