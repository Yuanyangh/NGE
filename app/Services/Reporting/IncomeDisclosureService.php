<?php

namespace App\Services\Reporting;

use App\DTOs\IncomeDisclosureData;
use App\Models\User;
use App\Scopes\CompanyScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class IncomeDisclosureService
{
    /**
     * Earnings brackets for the income disclosure report.
     * Each entry: [label, min (inclusive), max (exclusive, null = no upper bound)]
     *
     * Protected so subclasses can override per-company if needed.
     * Could also be moved to company_settings as JSON if per-company customization is required.
     */
    protected const BRACKETS = [
        ['label' => '$0',            'min' => '0',     'max' => '0.01'],
        ['label' => '$1 – $100',     'min' => '0.01',  'max' => '100.01'],
        ['label' => '$101 – $500',   'min' => '100.01','max' => '500.01'],
        ['label' => '$501 – $1,000', 'min' => '500.01','max' => '1000.01'],
        ['label' => '$1,001 – $5,000','min' => '1000.01','max' => '5000.01'],
        ['label' => '$5,001 – $10,000','min' => '5000.01','max' => '10000.01'],
        ['label' => '$10,001+',      'min' => '10000.01','max' => null],
    ];

    public function generate(int $companyId, Carbon $startDate, Carbon $endDate): IncomeDisclosureData
    {
        // 1. Count all affiliates for this company
        $totalAffiliates = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $companyId)
            ->where('role', 'affiliate')
            ->count();

        if ($totalAffiliates === 0) {
            return $this->buildEmptyResult($companyId, $startDate, $endDate);
        }

        // 2. Aggregate earnings per affiliate (commission + bonus) in the date range
        //    Use a subquery union so we never load all rows into PHP memory.
        $startStr = $startDate->toDateTimeString();
        $endStr   = $endDate->copy()->endOfDay()->toDateTimeString();

        // commission_ledger_entries sums
        $commissionSums = DB::table('commission_ledger_entries')
            ->select('user_id', DB::raw('SUM(amount) as earned'))
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$startStr, $endStr])
            ->groupBy('user_id');

        // bonus_ledger_entries sums
        $bonusSums = DB::table('bonus_ledger_entries')
            ->select('user_id', DB::raw('SUM(amount) as earned'))
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$startStr, $endStr])
            ->groupBy('user_id');

        // Union and re-aggregate by user_id
        $earningsPerAffiliate = DB::table(
            DB::raw("({$commissionSums->toSql()} UNION ALL {$bonusSums->toSql()}) as ledger_union")
        )
            ->mergeBindings($commissionSums)
            ->mergeBindings($bonusSums)
            ->select('user_id', DB::raw('SUM(earned) as total_earned'))
            ->groupBy('user_id')
            ->get()
            ->pluck('total_earned', 'user_id')
            ->map(fn ($v) => (string) $v)
            ->toArray();

        // 3. Build full affiliate list — those not in $earningsPerAffiliate earned $0
        $affiliateIds = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $companyId)
            ->where('role', 'affiliate')
            ->pluck('id')
            ->toArray();

        // Build a complete earnings array (one entry per affiliate)
        $allEarnings = [];
        foreach ($affiliateIds as $userId) {
            $allEarnings[] = isset($earningsPerAffiliate[$userId])
                ? $earningsPerAffiliate[$userId]
                : '0';
        }

        // Sort descending for percentile calculations
        usort($allEarnings, fn ($a, $b) => bccomp($b, $a, 4));

        // 4. Aggregate statistics
        $totalPaidOut  = array_reduce($allEarnings, fn ($carry, $v) => bcadd($carry, $v, 4), '0');
        $activeCount   = count(array_filter($allEarnings, fn ($v) => bccomp($v, '0', 4) > 0));
        $inactiveCount = $totalAffiliates - $activeCount;

        $meanEarnings = $totalAffiliates > 0
            ? bcdiv($totalPaidOut, (string) $totalAffiliates, 4)
            : '0';

        $medianEarnings = $this->calculateMedian($allEarnings);
        $top1Threshold  = $this->calculatePercentileThreshold($allEarnings, 1);
        $top10Threshold = $this->calculatePercentileThreshold($allEarnings, 10);

        // 5. Build brackets
        $brackets = $this->buildBrackets($allEarnings, $totalAffiliates);

        // Compute derived percentages using bcmath
        $zeroEarnerPercentage = $totalAffiliates > 0
            ? bcdiv(bcmul((string) $inactiveCount, '100', 1), (string) $totalAffiliates, 1)
            : '0.0';
        $activePercentage = $totalAffiliates > 0
            ? bcdiv(bcmul((string) $activeCount, '100', 1), (string) $totalAffiliates, 1)
            : '0.0';

        return new IncomeDisclosureData(
            totalAffiliates: $totalAffiliates,
            activeAffiliates: $activeCount,
            inactiveAffiliates: $inactiveCount,
            medianEarnings: $this->roundToTwo($medianEarnings),
            meanEarnings: $this->roundToTwo($meanEarnings),
            totalPaidOut: $this->roundToTwo($totalPaidOut),
            top1PercentThreshold: $this->roundToTwo($top1Threshold),
            top10PercentThreshold: $this->roundToTwo($top10Threshold),
            brackets: $brackets,
            periodStart: $startDate->toDateString(),
            periodEnd: $endDate->toDateString(),
            zeroEarnerCount: $inactiveCount,
            zeroEarnerPercentage: $zeroEarnerPercentage,
            activePercentage: $activePercentage,
        );
    }

    /**
     * Calculate the median of a sorted (descending) array of bcmath strings.
     */
    private function calculateMedian(array $sortedDesc): string
    {
        $count = count($sortedDesc);
        if ($count === 0) {
            return '0';
        }

        if ($count % 2 === 1) {
            return $sortedDesc[(int) floor($count / 2)];
        }

        $mid1 = $sortedDesc[$count / 2 - 1];
        $mid2 = $sortedDesc[$count / 2];

        return bcdiv(bcadd($mid1, $mid2, 4), '2', 4);
    }

    /**
     * Return the earnings threshold above which the top N% of affiliates sit.
     * Array is sorted descending.
     */
    private function calculatePercentileThreshold(array $sortedDesc, int $topPercent): string
    {
        $count = count($sortedDesc);
        if ($count === 0) {
            return '0';
        }

        // How many affiliates are in the top N%
        $topCount = (int) ceil($count * $topPercent / 100);
        $topCount = max(1, $topCount);

        // The threshold is the lowest earnings value still in the top N%
        // i.e. the earnings at index $topCount - 1 (0-indexed, descending)
        $index = min($topCount - 1, $count - 1);

        return $sortedDesc[$index];
    }

    /**
     * Build the earnings distribution brackets.
     */
    private function buildBrackets(array $sortedDesc, int $totalAffiliates): array
    {
        $brackets = [];

        foreach (static::BRACKETS as $bracket) {
            $min    = $bracket['min'];
            $maxRaw = $bracket['max'];
            $count  = 0;

            foreach ($sortedDesc as $earned) {
                $aboveMin = bccomp($earned, $min, 4) >= 0;

                if ($maxRaw !== null) {
                    $belowMax = bccomp($earned, $maxRaw, 4) < 0;
                    if ($aboveMin && $belowMax) {
                        $count++;
                    }
                } else {
                    if ($aboveMin) {
                        $count++;
                    }
                }
            }

            $percentage = $totalAffiliates > 0
                ? bcdiv(bcmul((string) $count, '100', 1), (string) $totalAffiliates, 1)
                : '0.0';

            $brackets[] = [
                'label'      => $bracket['label'],
                'count'      => $count,
                'percentage' => $percentage,
            ];
        }

        return $brackets;
    }

    private function roundToTwo(string $value): string
    {
        return bcadd($value, '0', 2);
    }

    private function buildEmptyResult(int $companyId, Carbon $startDate, Carbon $endDate): IncomeDisclosureData
    {
        $emptyBrackets = array_map(fn ($b) => [
            'label'      => $b['label'],
            'count'      => 0,
            'percentage' => '0.0',
        ], static::BRACKETS);

        return new IncomeDisclosureData(
            totalAffiliates: 0,
            activeAffiliates: 0,
            inactiveAffiliates: 0,
            medianEarnings: '0.00',
            meanEarnings: '0.00',
            totalPaidOut: '0.00',
            top1PercentThreshold: '0.00',
            top10PercentThreshold: '0.00',
            brackets: $emptyBrackets,
            periodStart: $startDate->toDateString(),
            periodEnd: $endDate->toDateString(),
            zeroEarnerCount: 0,
            zeroEarnerPercentage: '0.0',
            activePercentage: '0.0',
        );
    }
}
