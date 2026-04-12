<?php

namespace App\Services\Compliance;

use App\DTOs\ChurnRiskResult;
use App\Models\CompanySetting;
use App\Models\GenealogyNode;
use App\Models\Transaction;
use App\Models\User;
use App\Scopes\CompanyScope;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChurnDetector
{
    /**
     * Scan all affiliates and categorize by churn risk level.
     */
    public function scan(int $companyId, Carbon $date): Collection
    {
        $atRiskDays = (int) CompanySetting::getValue($companyId, 'churn_at_risk_days', '30');
        $inactiveDays = (int) CompanySetting::getValue($companyId, 'churn_inactive_days', '60');
        $volumeDeclinePct = CompanySetting::getValue($companyId, 'churn_volume_decline_pct', '50');
        $stagnantLeaderDays = (int) CompanySetting::getValue($companyId, 'churn_stagnant_leader_days', '60');

        // Get all active affiliates
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

        // Last order date per affiliate (as buyer)
        $lastOrders = Transaction::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $companyId)
            ->whereIn('user_id', $affiliateIds)
            ->whereIn('type', ['purchase', 'smartship'])
            ->where('status', 'confirmed')
            ->groupBy('user_id')
            ->select('user_id', DB::raw('MAX(transaction_date) as last_order_date'))
            ->pluck('last_order_date', 'user_id')
            ->all();

        // Current 30-day volume per affiliate (personal purchases as buyer)
        $currentWindowStart = $date->copy()->subDays(29)->toDateString();
        $currentWindowEnd = $date->toDateString();

        $currentVolumes = Transaction::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $companyId)
            ->whereIn('user_id', $affiliateIds)
            ->whereIn('type', ['purchase', 'smartship'])
            ->where('status', 'confirmed')
            ->where('qualifies_for_commission', true)
            ->whereDate('transaction_date', '>=', $currentWindowStart)
            ->whereDate('transaction_date', '<=', $currentWindowEnd)
            ->groupBy('user_id')
            ->select('user_id', DB::raw('SUM(xp) as total_xp'))
            ->pluck('total_xp', 'user_id')
            ->all();

        // Previous 30-day volume (the 30 days before the current window)
        $previousWindowStart = $date->copy()->subDays(59)->toDateString();
        $previousWindowEnd = $date->copy()->subDays(30)->toDateString();

        $previousVolumes = Transaction::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $companyId)
            ->whereIn('user_id', $affiliateIds)
            ->whereIn('type', ['purchase', 'smartship'])
            ->where('status', 'confirmed')
            ->where('qualifies_for_commission', true)
            ->whereDate('transaction_date', '>=', $previousWindowStart)
            ->whereDate('transaction_date', '<=', $previousWindowEnd)
            ->groupBy('user_id')
            ->select('user_id', DB::raw('SUM(xp) as total_xp'))
            ->pluck('total_xp', 'user_id')
            ->all();

        // Stagnant leader check: get affiliates who have downline (sponsor_id points to their node)
        // First, get genealogy node IDs for our affiliates
        $nodesByUser = GenealogyNode::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $companyId)
            ->whereIn('user_id', $affiliateIds)
            ->pluck('id', 'user_id')
            ->all();

        // Find which affiliates have at least one child node (are leaders)
        $leaderNodeIds = [];
        if (!empty($nodesByUser)) {
            $leaderNodeIds = GenealogyNode::withoutGlobalScope(CompanyScope::class)
                ->where('company_id', $companyId)
                ->whereIn('sponsor_id', array_values($nodesByUser))
                ->select('sponsor_id')
                ->distinct()
                ->pluck('sponsor_id')
                ->all();
        }

        // Map leader node IDs back to user IDs
        $nodeIdToUser = array_flip($nodesByUser);
        $leaderUserIds = array_map(fn ($nodeId) => $nodeIdToUser[$nodeId] ?? null, $leaderNodeIds);
        $leaderUserIds = array_filter($leaderUserIds);

        // For leaders, get last downline order date
        // Downline = users whose genealogy node has sponsor_id pointing to the leader's node
        $lastDownlineOrders = [];
        if (!empty($leaderUserIds)) {
            foreach ($leaderUserIds as $leaderUserId) {
                $leaderNodeId = $nodesByUser[$leaderUserId] ?? null;
                if ($leaderNodeId === null) {
                    continue;
                }

                // Get all descendant user_ids (direct + indirect via closure table or recursive)
                $descendantUserIds = GenealogyNode::withoutGlobalScope(CompanyScope::class)
                    ->where('company_id', $companyId)
                    ->where('id', $leaderNodeId)
                    ->first()
                    ?->descendants()
                    ->pluck('user_id')
                    ->all() ?? [];

                if (empty($descendantUserIds)) {
                    continue;
                }

                $lastDownlineOrder = Transaction::withoutGlobalScope(CompanyScope::class)
                    ->where('company_id', $companyId)
                    ->whereIn('user_id', $descendantUserIds)
                    ->whereIn('type', ['purchase', 'smartship'])
                    ->where('status', 'confirmed')
                    ->max('transaction_date');

                if ($lastDownlineOrder !== null) {
                    $lastDownlineOrders[$leaderUserId] = $lastDownlineOrder;
                }
            }
        }

        $results = collect();

        foreach ($affiliateIds as $affiliateId) {
            $lastOrderDate = isset($lastOrders[$affiliateId])
                ? Carbon::parse($lastOrders[$affiliateId])
                : null;

            $daysSinceLastOrder = $lastOrderDate !== null
                ? (int) $lastOrderDate->diffInDays($date)
                : null;

            // Check inactivity (higher severity first)
            if ($daysSinceLastOrder !== null && $daysSinceLastOrder >= $inactiveDays) {
                $results->push(new ChurnRiskResult(
                    user_id: $affiliateId,
                    user_name: $affiliateNames[$affiliateId] ?? '',
                    risk_level: 'inactive_warning',
                    reason: "No orders in {$daysSinceLastOrder} days (threshold: {$inactiveDays} days)",
                    days_since_last_order: $daysSinceLastOrder,
                    current_volume: null,
                    previous_volume: null,
                    volume_change_pct: null,
                ));
                continue; // Skip at_risk — inactive_warning is more severe
            }

            if ($daysSinceLastOrder !== null && $daysSinceLastOrder >= $atRiskDays) {
                $results->push(new ChurnRiskResult(
                    user_id: $affiliateId,
                    user_name: $affiliateNames[$affiliateId] ?? '',
                    risk_level: 'at_risk',
                    reason: "No orders in {$daysSinceLastOrder} days (threshold: {$atRiskDays} days)",
                    days_since_last_order: $daysSinceLastOrder,
                    current_volume: null,
                    previous_volume: null,
                    volume_change_pct: null,
                ));
                continue;
            }

            // Never ordered at all — treat as at_risk if they exist
            if ($lastOrderDate === null) {
                $results->push(new ChurnRiskResult(
                    user_id: $affiliateId,
                    user_name: $affiliateNames[$affiliateId] ?? '',
                    risk_level: 'at_risk',
                    reason: 'No orders recorded',
                    days_since_last_order: null,
                    current_volume: null,
                    previous_volume: null,
                    volume_change_pct: null,
                ));
                continue;
            }

            // Check volume decline
            $currentVol = (string) ($currentVolumes[$affiliateId] ?? '0');
            $previousVol = (string) ($previousVolumes[$affiliateId] ?? '0');

            if (bccomp($previousVol, '0', 4) > 0) {
                $decline = bcsub($previousVol, $currentVol, 4);
                if (bccomp($decline, '0', 4) > 0) {
                    $declinePct = bcmul(bcdiv($decline, $previousVol, 6), '100', 2);
                    if (bccomp($declinePct, $volumeDeclinePct, 2) > 0) {
                        $results->push(new ChurnRiskResult(
                            user_id: $affiliateId,
                            user_name: $affiliateNames[$affiliateId] ?? '',
                            risk_level: 'declining',
                            reason: "Volume declined {$declinePct}% month-over-month (threshold: {$volumeDeclinePct}%)",
                            days_since_last_order: $daysSinceLastOrder,
                            current_volume: $currentVol,
                            previous_volume: $previousVol,
                            volume_change_pct: '-' . $declinePct,
                        ));
                        continue;
                    }
                }
            }

            // Check stagnant leader
            if (in_array($affiliateId, $leaderUserIds, true)) {
                $lastDownlineDate = isset($lastDownlineOrders[$affiliateId])
                    ? Carbon::parse($lastDownlineOrders[$affiliateId])
                    : null;

                $daysSinceDownlineOrder = $lastDownlineDate !== null
                    ? (int) $lastDownlineDate->diffInDays($date)
                    : null;

                if ($daysSinceDownlineOrder !== null && $daysSinceDownlineOrder >= $stagnantLeaderDays) {
                    $results->push(new ChurnRiskResult(
                        user_id: $affiliateId,
                        user_name: $affiliateNames[$affiliateId] ?? '',
                        risk_level: 'stagnant_leader',
                        reason: "No downline orders in {$daysSinceDownlineOrder} days (threshold: {$stagnantLeaderDays} days)",
                        days_since_last_order: $daysSinceLastOrder,
                        current_volume: $currentVol,
                        previous_volume: $previousVol,
                        volume_change_pct: null,
                    ));
                } elseif ($daysSinceDownlineOrder === null) {
                    $results->push(new ChurnRiskResult(
                        user_id: $affiliateId,
                        user_name: $affiliateNames[$affiliateId] ?? '',
                        risk_level: 'stagnant_leader',
                        reason: "No downline orders recorded (threshold: {$stagnantLeaderDays} days)",
                        days_since_last_order: $daysSinceLastOrder,
                        current_volume: $currentVol,
                        previous_volume: $previousVol,
                        volume_change_pct: null,
                    ));
                }
            }
        }

        return $results;
    }
}
