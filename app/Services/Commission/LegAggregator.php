<?php

namespace App\Services\Commission;

use App\DTOs\PlanConfig;
use App\Models\GenealogyNode;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

class LegAggregator
{
    /**
     * Get the viral volume for each "leg" of an affiliate's downline.
     *
     * Each direct referral and their entire subtree forms one "leg."
     * Returns an array of ['leg_root_user_id' => int, 'volume' => string].
     */
    public function getLegVolumes(User $affiliate, Carbon $date, PlanConfig $config): array
    {
        $windowStart = $date->copy()->subDays($config->rolling_days - 1);

        // Find the affiliate's genealogy node
        $affiliateNode = GenealogyNode::withoutGlobalScopes()
            ->where('user_id', $affiliate->id)
            ->where('company_id', $affiliate->company_id)
            ->first();

        if (! $affiliateNode) {
            return [];
        }

        // Get direct children (each child = one leg root)
        $directChildren = GenealogyNode::withoutGlobalScopes()
            ->where('sponsor_id', $affiliateNode->id)
            ->where('company_id', $affiliate->company_id)
            ->get();

        $legs = [];

        foreach ($directChildren as $childNode) {
            // Get all descendant IDs in this leg (child + their entire subtree)
            $subtreeUserIds = $this->getSubtreeUserIds($childNode);

            // Sum all XP from confirmed qualifying transactions by users in this subtree
            $volume = Transaction::withoutGlobalScopes()
                ->where('company_id', $affiliate->company_id)
                ->whereIn('user_id', $subtreeUserIds)
                ->where('status', 'confirmed')
                ->where('qualifies_for_commission', true)
                ->whereBetween('transaction_date', [$windowStart->toDateString(), $date->toDateString()])
                ->sum('xp');

            $legs[] = [
                'leg_root_user_id' => $childNode->user_id,
                'volume' => (string) $volume,
            ];
        }

        return $legs;
    }

    /**
     * Get all user IDs in a subtree rooted at the given node (inclusive).
     */
    private function getSubtreeUserIds(GenealogyNode $node): array
    {
        // Use the adjacency list package's descendantsAndSelf relationship
        $userIds = $node->descendantsAndSelf()
            ->pluck('user_id')
            ->toArray();

        return $userIds;
    }
}
