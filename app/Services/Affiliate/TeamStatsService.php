<?php

namespace App\Services\Affiliate;

use App\DTOs\LegHealthData;
use App\DTOs\PlanConfig;
use App\DTOs\TeamStatsData;
use App\Models\GenealogyNode;
use App\Models\Transaction;
use App\Models\User;
use App\Scopes\CompanyScope;
use App\Services\Commission\LegAggregator;
use App\Services\Commission\QvvCalculator;
use Carbon\Carbon;

class TeamStatsService
{
    public function __construct(
        private LegAggregator $legAggregator,
        private QvvCalculator $qvvCalculator,
    ) {}

    public function calculate(User $affiliate, Carbon $date, PlanConfig $config): TeamStatsData
    {
        $windowStart = $date->copy()->subDays($config->rolling_days - 1);

        // Get affiliate's genealogy node
        $affiliateNode = GenealogyNode::withoutGlobalScope(CompanyScope::class)
            ->where('user_id', $affiliate->id)
            ->where('company_id', $affiliate->company_id)
            ->first();

        if (! $affiliateNode) {
            return new TeamStatsData(
                total_team_size: 0,
                active_affiliates: 0,
                active_customers: 0,
                total_team_volume_30d: '0',
                legs: [],
                qvv_capping_warning: false,
            );
        }

        // Get all descendants in one query
        $descendants = $affiliateNode->descendants()->get();
        $descendantUserIds = $descendants->pluck('user_id')->toArray();

        // Total team size (excluding self)
        $totalTeamSize = count($descendantUserIds);

        // Active affiliates in team
        $activeAffiliates = User::withoutGlobalScope(CompanyScope::class)
            ->whereIn('id', $descendantUserIds)
            ->where('company_id', $affiliate->company_id)
            ->where('role', 'affiliate')
            ->where('status', 'active')
            ->count();

        // Active customers: distinct users in team who have qualifying transactions in window
        $activeCustomers = Transaction::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $affiliate->company_id)
            ->whereIn('user_id', $descendantUserIds)
            ->where('status', 'confirmed')
            ->where('qualifies_for_commission', true)
            ->whereDate('transaction_date', '>=', $windowStart->toDateString())
            ->whereDate('transaction_date', '<=', $date->toDateString())
            ->where('xp', '>=', $config->active_customer_min_order_xp)
            ->distinct('user_id')
            ->count('user_id');

        // Total team volume
        $totalTeamVolume = (string) Transaction::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $affiliate->company_id)
            ->whereIn('user_id', $descendantUserIds)
            ->where('status', 'confirmed')
            ->where('qualifies_for_commission', true)
            ->whereDate('transaction_date', '>=', $windowStart->toDateString())
            ->whereDate('transaction_date', '<=', $date->toDateString())
            ->sum('xp');

        // Leg data
        $legVolumes = $this->legAggregator->getLegVolumes($affiliate, $date, $config);
        $volumeSnapshot = $this->qvvCalculator->calculate($legVolumes, $config);

        // Pre-fetch all leg root users in one query
        $legRootUserIds = array_column($legVolumes, 'leg_root_user_id');
        $legRootUsers = User::withoutGlobalScope(CompanyScope::class)
            ->whereIn('id', $legRootUserIds)
            ->get()
            ->keyBy('id');

        // Pre-fetch all direct children nodes (leg roots) and their subtrees
        $directChildren = $descendants->where('sponsor_id', $affiliateNode->id);
        $descendantsByLegRoot = [];
        foreach ($directChildren as $childNode) {
            // Collect all nodes in this subtree from the pre-fetched descendants
            $subtreeIds = $this->collectSubtreeUserIds($childNode, $descendants);
            $descendantsByLegRoot[$childNode->user_id] = $subtreeIds;
        }

        // Batch query: active customer counts for ALL legs at once
        $allLegUserIds = array_merge([], ...array_values($descendantsByLegRoot));
        $activeCountsByUser = [];
        if (! empty($allLegUserIds)) {
            $activeCountsByUser = Transaction::withoutGlobalScope(CompanyScope::class)
                ->where('company_id', $affiliate->company_id)
                ->whereIn('user_id', $allLegUserIds)
                ->where('status', 'confirmed')
                ->where('qualifies_for_commission', true)
                ->whereDate('transaction_date', '>=', $windowStart->toDateString())
                ->whereDate('transaction_date', '<=', $date->toDateString())
                ->where('xp', '>=', $config->active_customer_min_order_xp)
                ->distinct()
                ->pluck('user_id')
                ->flip()
                ->toArray();
        }

        // Find max volume for health labels
        $maxVolume = '0';
        foreach ($legVolumes as $leg) {
            if (bccomp($leg['volume'], $maxVolume, 4) > 0) {
                $maxVolume = $leg['volume'];
            }
        }

        // Build leg health data (no more queries per leg)
        $legs = [];
        foreach ($legVolumes as $leg) {
            $legRootUser = $legRootUsers->get($leg['leg_root_user_id']);
            $subtreeUserIds = $descendantsByLegRoot[$leg['leg_root_user_id']] ?? [];

            // Count active users in this leg from pre-fetched data
            $legActiveCount = 0;
            foreach ($subtreeUserIds as $uid) {
                if (isset($activeCountsByUser[$uid])) {
                    $legActiveCount++;
                }
            }

            $isLargeLeg = bccomp($leg['volume'], '0', 4) > 0
                && bccomp($leg['volume'], $maxVolume, 4) === 0;

            $healthLabel = $this->getHealthLabel($leg['volume'], $maxVolume);

            $legs[] = new LegHealthData(
                leg_root_user_id: $leg['leg_root_user_id'],
                leg_root_name: $legRootUser?->name ?? 'Unknown',
                volume: $leg['volume'],
                active_count: $legActiveCount,
                health_label: $healthLabel,
                is_large_leg: $isLargeLeg,
                is_capping_qvv: $isLargeLeg && $volumeSnapshot->was_capped,
            );
        }

        return new TeamStatsData(
            total_team_size: $totalTeamSize,
            active_affiliates: $activeAffiliates,
            active_customers: $activeCustomers,
            total_team_volume_30d: $totalTeamVolume,
            legs: $legs,
            qvv_capping_warning: $volumeSnapshot->was_capped,
        );
    }

    /**
     * Collect all user_ids in a subtree from pre-fetched node collection.
     */
    private function collectSubtreeUserIds(GenealogyNode $root, \Illuminate\Support\Collection $allNodes): array
    {
        $nodesById = $allNodes->keyBy('id');
        $nodesByParent = $allNodes->groupBy('sponsor_id');
        $result = [$root->user_id];

        $queue = [$root->id];
        while (! empty($queue)) {
            $parentId = array_shift($queue);
            $children = $nodesByParent->get($parentId, collect());
            foreach ($children as $child) {
                $result[] = $child->user_id;
                $queue[] = $child->id;
            }
        }

        return $result;
    }

    private function getHealthLabel(string $volume, string $maxVolume): string
    {
        if (bccomp($maxVolume, '0', 4) <= 0) {
            return 'weak';
        }

        $ratio = bcdiv($volume, $maxVolume, 4);

        if (bccomp($ratio, '0.6', 4) >= 0) {
            return 'strong';
        }
        if (bccomp($ratio, '0.3', 4) >= 0) {
            return 'moderate';
        }

        return 'weak';
    }
}
