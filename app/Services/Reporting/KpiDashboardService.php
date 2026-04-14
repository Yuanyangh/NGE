<?php

namespace App\Services\Reporting;

use App\DTOs\KpiDashboardData;
use App\Models\User;
use App\Scopes\CompanyScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class KpiDashboardService
{
    public function generate(int $companyId, Carbon $startDate, Carbon $endDate): KpiDashboardData
    {
        $startStr = $startDate->toDateString();
        $endStr = $endDate->toDateString();
        $startDt = $startDate->copy()->startOfDay()->toDateTimeString();
        $endDt = $endDate->copy()->endOfDay()->toDateTimeString();

        // --- Totals from commission runs ---
        $runStats = DB::table('commission_runs')
            ->where('company_id', $companyId)
            ->whereBetween('run_date', [$startStr, $endStr])
            ->where('status', 'completed')
            ->selectRaw('COUNT(*) as run_count')
            ->selectRaw('COALESCE(SUM(total_company_volume), 0) as total_volume')
            ->selectRaw('COALESCE(SUM(total_affiliate_commission), 0) as total_aff_comm')
            ->selectRaw('COALESCE(SUM(total_viral_commission), 0) as total_viral_comm')
            ->selectRaw('COALESCE(SUM(total_bonus_amount), 0) as total_bonus')
            ->selectRaw('SUM(CASE WHEN viral_cap_triggered = 1 THEN 1 ELSE 0 END) as viral_cap_count')
            ->first();

        $totalVolume = (string) ($runStats->total_volume ?? '0');
        $totalAffComm = (string) ($runStats->total_aff_comm ?? '0');
        $totalViralComm = (string) ($runStats->total_viral_comm ?? '0');
        $totalBonuses = (string) ($runStats->total_bonus ?? '0');
        $commissionRunCount = (int) ($runStats->run_count ?? 0);
        $viralCapTriggeredCount = (int) ($runStats->viral_cap_count ?? 0);

        $totalCommissions = bcadd($totalAffComm, $totalViralComm, 4);
        $totalPayout = bcadd($totalCommissions, $totalBonuses, 4);

        $payoutRatio = bccomp($totalVolume, '0', 4) > 0
            ? bcmul(bcdiv($totalPayout, $totalVolume, 6), '100', 2)
            : '0.00';

        // --- Affiliate counts ---
        $totalAffiliates = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $companyId)
            ->where('role', 'affiliate')
            ->count();

        $commissionEarners = DB::table('commission_ledger_entries')
            ->join('commission_runs', 'commission_runs.id', '=', 'commission_ledger_entries.commission_run_id')
            ->where('commission_ledger_entries.company_id', $companyId)
            ->whereBetween('commission_runs.run_date', [$startStr, $endStr])
            ->distinct()
            ->pluck('commission_ledger_entries.user_id');

        $bonusEarners = DB::table('bonus_ledger_entries')
            ->join('commission_runs', 'commission_runs.id', '=', 'bonus_ledger_entries.commission_run_id')
            ->where('bonus_ledger_entries.company_id', $companyId)
            ->whereBetween('commission_runs.run_date', [$startStr, $endStr])
            ->distinct()
            ->pluck('bonus_ledger_entries.user_id');

        $activeAffiliates = $commissionEarners->merge($bonusEarners)->unique()->count();

        // --- Active customers ---
        $activeCustomers = DB::table('transactions')
            ->where('company_id', $companyId)
            ->whereBetween('transaction_date', [$startStr, $endStr])
            ->where('status', 'confirmed')
            ->where('qualifies_for_commission', true)
            ->distinct('user_id')
            ->count('user_id');

        // --- New enrollments ---
        $newEnrollments = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $companyId)
            ->whereBetween('enrolled_at', [$startDt, $endDt])
            ->count();

        // --- Top 5 earners ---
        $topEarners = $this->getTopEarners($companyId, $startStr, $endStr, 5);

        // --- Volume trend ---
        $volumeTrend = DB::table('transactions')
            ->where('company_id', $companyId)
            ->whereBetween('transaction_date', [$startStr, $endStr])
            ->where('status', 'confirmed')
            ->where('type', '!=', 'refund')
            ->groupBy('transaction_date')
            ->orderBy('transaction_date')
            ->select('transaction_date as date', DB::raw('COALESCE(SUM(xp), 0) as amount'))
            ->get()
            ->map(fn ($row) => ['date' => $row->date, 'amount' => (string) $row->amount])
            ->toArray();

        // --- Payout trend ---
        $payoutTrend = DB::table('commission_runs')
            ->where('company_id', $companyId)
            ->whereBetween('run_date', [$startStr, $endStr])
            ->where('status', 'completed')
            ->groupBy('run_date')
            ->orderBy('run_date')
            ->select(
                'run_date as date',
                DB::raw('COALESCE(SUM(total_affiliate_commission), 0) + COALESCE(SUM(total_viral_commission), 0) + COALESCE(SUM(total_bonus_amount), 0) as amount')
            )
            ->get()
            ->map(fn ($row) => ['date' => $row->date, 'amount' => (string) $row->amount])
            ->toArray();

        // --- Period comparison ---
        $periodLengthDays = $startDate->diffInDays($endDate) + 1;
        $prevEnd = $startDate->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays($periodLengthDays - 1);

        $prevRunStats = DB::table('commission_runs')
            ->where('company_id', $companyId)
            ->whereBetween('run_date', [$prevStart->toDateString(), $prevEnd->toDateString()])
            ->where('status', 'completed')
            ->selectRaw('COALESCE(SUM(total_company_volume), 0) as total_volume')
            ->selectRaw('COALESCE(SUM(total_affiliate_commission), 0) + COALESCE(SUM(total_viral_commission), 0) as total_comm')
            ->first();

        $prevVolume = (string) ($prevRunStats->total_volume ?? '0');
        $prevCommissions = (string) ($prevRunStats->total_comm ?? '0');

        $prevActiveAffiliates = DB::table('commission_ledger_entries')
            ->join('commission_runs', 'commission_runs.id', '=', 'commission_ledger_entries.commission_run_id')
            ->where('commission_ledger_entries.company_id', $companyId)
            ->whereBetween('commission_runs.run_date', [$prevStart->toDateString(), $prevEnd->toDateString()])
            ->distinct('commission_ledger_entries.user_id')
            ->count('commission_ledger_entries.user_id');

        $prevEnrollments = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $companyId)
            ->whereBetween('enrolled_at', [
                $prevStart->startOfDay()->toDateTimeString(),
                $prevEnd->copy()->endOfDay()->toDateTimeString(),
            ])
            ->count();

        $volumeChange = $this->percentChange($prevVolume, $totalVolume);
        $commissionChange = $this->percentChange($prevCommissions, $totalCommissions);
        $affiliateChange = $this->percentChange((string) $prevActiveAffiliates, (string) $activeAffiliates);
        $enrollmentChange = $this->percentChange((string) $prevEnrollments, (string) $newEnrollments);

        return new KpiDashboardData(
            totalVolume: bcadd($totalVolume, '0', 2),
            totalCommissions: bcadd($totalCommissions, '0', 2),
            totalBonuses: bcadd($totalBonuses, '0', 2),
            payoutRatio: $payoutRatio,
            activeAffiliates: $activeAffiliates,
            totalAffiliates: $totalAffiliates,
            activeCustomers: $activeCustomers,
            newEnrollments: $newEnrollments,
            commissionRunCount: $commissionRunCount,
            viralCapTriggeredCount: $viralCapTriggeredCount,
            topEarners: $topEarners,
            volumeTrend: $volumeTrend,
            payoutTrend: $payoutTrend,
            volumeChange: $volumeChange,
            commissionChange: $commissionChange,
            affiliateChange: $affiliateChange,
            enrollmentChange: $enrollmentChange,
            periodStart: $startDate->toDateString(),
            periodEnd: $endDate->toDateString(),
        );
    }

    private function getTopEarners(int $companyId, string $startStr, string $endStr, int $limit): array
    {
        // Two separate queries merged in PHP — avoids UNION ALL + toSql() issues with SQLite
        $commissionEarnings = DB::table('commission_ledger_entries')
            ->join('commission_runs', 'commission_runs.id', '=', 'commission_ledger_entries.commission_run_id')
            ->where('commission_ledger_entries.company_id', $companyId)
            ->whereBetween('commission_runs.run_date', [$startStr, $endStr])
            ->groupBy('commission_ledger_entries.user_id')
            ->select('commission_ledger_entries.user_id', DB::raw('SUM(commission_ledger_entries.amount) as earned'))
            ->get()
            ->keyBy('user_id');

        $bonusEarnings = DB::table('bonus_ledger_entries')
            ->join('commission_runs', 'commission_runs.id', '=', 'bonus_ledger_entries.commission_run_id')
            ->where('bonus_ledger_entries.company_id', $companyId)
            ->whereBetween('commission_runs.run_date', [$startStr, $endStr])
            ->groupBy('bonus_ledger_entries.user_id')
            ->select('bonus_ledger_entries.user_id', DB::raw('SUM(bonus_ledger_entries.amount) as earned'))
            ->get()
            ->keyBy('user_id');

        // Merge earnings per user
        $allUserIds = $commissionEarnings->keys()->merge($bonusEarnings->keys())->unique();
        $merged = $allUserIds->map(function ($userId) use ($commissionEarnings, $bonusEarnings) {
            $comm = (string) ($commissionEarnings->get($userId)?->earned ?? '0');
            $bonus = (string) ($bonusEarnings->get($userId)?->earned ?? '0');
            return ['user_id' => $userId, 'total' => bcadd($comm, $bonus, 4)];
        })
        ->sortByDesc('total')
        ->take($limit)
        ->values();

        if ($merged->isEmpty()) {
            return [];
        }

        // Fetch names
        $userNames = DB::table('users')
            ->whereIn('id', $merged->pluck('user_id'))
            ->pluck('name', 'id');

        return $merged->map(fn ($row) => [
            'user_id' => (int) $row['user_id'],
            'name' => $userNames->get($row['user_id'], 'Unknown'),
            'total_earnings' => bcadd($row['total'], '0', 2),
        ])->toArray();
    }

    private function percentChange(string $previous, string $current): string
    {
        if (bccomp($previous, '0', 4) === 0) {
            return '0.00';
        }

        $diff = bcsub($current, $previous, 4);
        $ratio = bcdiv($diff, $previous, 6);

        return bcmul($ratio, '100', 2);
    }
}
