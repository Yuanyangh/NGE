<?php

namespace App\Services\Commission\Bonus;

use App\DTOs\BonusResult;
use App\Models\BonusType;
use App\Models\GenealogyNode;
use App\Scopes\CompanyScope;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MatchingBonusCalculator implements BonusCalculatorInterface
{
    /**
     * Calculate matching bonus for each affiliate based on their downline's commissions.
     *
     * For each affiliate, look at their direct downline's commission results (generation 1),
     * then their downline's downline (generation 2), etc. Apply generation-based rates
     * from BonusTier records.
     */
    public function calculate(
        BonusType $bonusType,
        Collection $affiliates,
        Collection $commissionResults,
        Carbon $date,
    ): Collection {
        // Inactive bonus type returns empty
        if (! $bonusType->is_active) {
            return collect();
        }

        $tiers = $bonusType->tiers()->orderBy('level')->get();

        if ($tiers->isEmpty()) {
            return collect();
        }

        // Build config map
        $configMap = $bonusType->configs()->pluck('value', 'key')->toArray();
        $maxGenerations = (int) ($configMap['max_generations'] ?? $tiers->count());

        // Build generation rate map: level => rate
        $generationRates = [];
        foreach ($tiers as $tier) {
            $generationRates[$tier->level] = (string) $tier->rate;
        }

        // Index commission results by user_id
        // Commission total = affiliate_commission + viral_commission
        $commissionByUser = [];
        foreach ($commissionResults as $result) {
            $totalComm = bcadd($result['affiliate_commission'] ?? '0', $result['viral_commission'] ?? '0', 4);
            $commissionByUser[$result['user_id']] = $totalComm;
        }

        // Build sponsor tree from genealogy nodes
        $companyId = $affiliates->first()?->company_id;
        if (! $companyId) {
            return collect();
        }

        $nodes = GenealogyNode::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $companyId)
            ->get();

        // Maps: node_id => user_id, user_id => node_id, sponsor_node_id => [child_node_ids]
        $nodeToUser = [];
        $userToNode = [];
        $childrenByNode = [];

        foreach ($nodes as $node) {
            $nodeToUser[$node->id] = $node->user_id;
            $userToNode[$node->user_id] = $node->id;

            if ($node->sponsor_id !== null) {
                $childrenByNode[$node->sponsor_id][] = $node->id;
            }
        }

        $results = collect();

        foreach ($affiliates as $affiliate) {
            $affiliateNodeId = $userToNode[$affiliate->id] ?? null;
            if ($affiliateNodeId === null) {
                continue;
            }

            $matchingBonus = '0';
            $generationDetails = [];

            // BFS through generations
            $currentGenNodes = $childrenByNode[$affiliateNodeId] ?? [];

            for ($gen = 1; $gen <= $maxGenerations; $gen++) {
                if (empty($currentGenNodes)) {
                    break;
                }

                $rate = $generationRates[$gen] ?? null;
                if ($rate === null) {
                    break;
                }

                $genTotal = '0';
                $nextGenNodes = [];

                foreach ($currentGenNodes as $nodeId) {
                    $userId = $nodeToUser[$nodeId] ?? null;
                    if ($userId === null) {
                        continue;
                    }

                    $userComm = $commissionByUser[$userId] ?? '0';
                    if (bccomp($userComm, '0', 4) > 0) {
                        $genBonus = bcmul($userComm, $rate, 4);
                        $genTotal = bcadd($genTotal, $genBonus, 4);
                    }

                    // Collect next generation children
                    foreach ($childrenByNode[$nodeId] ?? [] as $childNodeId) {
                        $nextGenNodes[] = $childNodeId;
                    }
                }

                if (bccomp($genTotal, '0', 4) > 0) {
                    $generationDetails[] = [
                        'generation' => $gen,
                        'rate' => $rate,
                        'amount' => $genTotal,
                    ];
                    $matchingBonus = bcadd($matchingBonus, $genTotal, 4);
                }

                $currentGenNodes = $nextGenNodes;
            }

            if (bccomp($matchingBonus, '0', 4) > 0) {
                $results->push(new BonusResult(
                    user_id: $affiliate->id,
                    amount: $matchingBonus,
                    bonus_type_id: $bonusType->id,
                    tier_achieved: null,
                    qualification_snapshot: [
                        'bonus_type' => 'matching',
                        'generations_matched' => count($generationDetails),
                        'max_generations' => $maxGenerations,
                        'generation_details' => $generationDetails,
                    ],
                    description: sprintf(
                        'Matching bonus: %d generation(s), total $%s',
                        count($generationDetails),
                        $matchingBonus,
                    ),
                ));
            }
        }

        return $results;
    }
}
