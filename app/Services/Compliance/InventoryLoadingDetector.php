<?php

namespace App\Services\Compliance;

use App\DTOs\InventoryLoadingResult;
use App\Models\CompanySetting;
use App\Models\Transaction;
use App\Models\User;
use App\Scopes\CompanyScope;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryLoadingDetector
{
    /**
     * Scan all affiliates for potential inventory loading.
     * Returns a collection of flagged affiliates with their ratios.
     */
    public function scan(int $companyId, Carbon $date, int $rollingDays = 30): Collection
    {
        $threshold = CompanySetting::getValue($companyId, 'inventory_loading_threshold', '0.80');
        $windowStart = $date->copy()->subDays($rollingDays - 1)->toDateString();
        $windowEnd = $date->toDateString();

        // Get all active affiliates for this company
        $affiliates = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $companyId)
            ->where('role', 'affiliate')
            ->where('status', 'active')
            ->select('id', 'name')
            ->get();

        if ($affiliates->isEmpty()) {
            return collect();
        }

        $affiliateIds = $affiliates->pluck('id')->all();
        $affiliateNames = $affiliates->pluck('name', 'id')->all();

        // Personal purchases: SUM(xp) grouped by user_id for affiliates who are buyers
        $personalVolumes = Transaction::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $companyId)
            ->whereIn('user_id', $affiliateIds)
            ->whereIn('type', ['purchase', 'smartship'])
            ->where('status', 'confirmed')
            ->where('qualifies_for_commission', true)
            ->whereDate('transaction_date', '>=', $windowStart)
            ->whereDate('transaction_date', '<=', $windowEnd)
            ->groupBy('user_id')
            ->select('user_id', DB::raw('SUM(xp) as total_xp'))
            ->pluck('total_xp', 'user_id')
            ->all();

        // Referred volumes: SUM(xp) grouped by referred_by_user_id
        $referredVolumes = Transaction::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $companyId)
            ->whereIn('referred_by_user_id', $affiliateIds)
            ->where('status', 'confirmed')
            ->where('qualifies_for_commission', true)
            ->whereDate('transaction_date', '>=', $windowStart)
            ->whereDate('transaction_date', '<=', $windowEnd)
            ->groupBy('referred_by_user_id')
            ->select('referred_by_user_id', DB::raw('SUM(xp) as total_xp'))
            ->pluck('total_xp', 'referred_by_user_id')
            ->all();

        $flagged = collect();

        foreach ($affiliateIds as $affiliateId) {
            $personalVol = (string) ($personalVolumes[$affiliateId] ?? '0');
            $referredVol = (string) ($referredVolumes[$affiliateId] ?? '0');
            $totalVol = bcadd($personalVol, $referredVol, 4);

            // Skip affiliates with no activity
            if (bccomp($totalVol, '0', 4) === 0) {
                continue;
            }

            $ratio = bcdiv($personalVol, $totalVol, 4);

            if (bccomp($ratio, $threshold, 4) > 0) {
                $riskLevel = bccomp($ratio, '0.95', 4) > 0 ? 'critical' : 'warning';

                $flagged->push(new InventoryLoadingResult(
                    user_id: $affiliateId,
                    user_name: $affiliateNames[$affiliateId] ?? '',
                    personal_volume: $personalVol,
                    referred_volume: $referredVol,
                    total_volume: $totalVol,
                    ratio: $ratio,
                    threshold: $threshold,
                    risk_level: $riskLevel,
                ));
            }
        }

        return $flagged;
    }
}
