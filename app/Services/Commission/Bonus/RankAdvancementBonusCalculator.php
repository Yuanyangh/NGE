<?php

namespace App\Services\Commission\Bonus;

use App\DTOs\BonusResult;
use App\Enums\BonusTypeEnum;
use App\Models\BonusLedgerEntry;
use App\Models\BonusType;
use App\Scopes\CompanyScope;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RankAdvancementBonusCalculator implements BonusCalculatorInterface
{
    /**
     * Calculate rank advancement bonus for affiliates who achieve a new highest rank.
     *
     * Compares current rank (from qualification_snapshot.current_rank) against
     * historical highest rank from bonus_ledger_entries.
     * If current rank > historical highest, award the one-time bonus for that tier.
     * Only pays the HIGHEST new tier achieved, not all skipped tiers.
     *
     * Tiers use qualifier_type='rank' and qualifier_value = rank numeric threshold.
     * The qualification_snapshot on commission results must include 'current_rank'.
     */
    public function calculate(
        BonusType $bonusType,
        Collection $affiliates,
        Collection $commissionResults,
        Carbon $date,
    ): Collection {
        if (! $bonusType->is_active) {
            return collect();
        }

        $tiers = $bonusType->tiers()->orderBy('level')->get();

        if ($tiers->isEmpty()) {
            return collect();
        }

        // Index commission results by user_id
        $commByUser = [];
        foreach ($commissionResults as $result) {
            $commByUser[$result['user_id']] = $result;
        }

        // Build tier lookup by level
        $tiersByLevel = $tiers->keyBy('level');

        $affiliateIds = $affiliates->pluck('id')->toArray();
        $companyId = $affiliates->first()?->company_id;

        if (! $companyId) {
            return collect();
        }

        // Find all rank_advancement bonus type IDs for this company
        $rankAdvBonusTypeIds = BonusType::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $companyId)
            ->where('type', BonusTypeEnum::RankAdvancement)
            ->pluck('id')
            ->toArray();

        // Query historical highest tier_achieved per user from bonus_ledger_entries
        $historicalHighest = [];
        if (! empty($rankAdvBonusTypeIds)) {
            $entries = BonusLedgerEntry::withoutGlobalScope(CompanyScope::class)
                ->where('company_id', $companyId)
                ->whereIn('bonus_type_id', $rankAdvBonusTypeIds)
                ->whereIn('user_id', $affiliateIds)
                ->whereNotNull('tier_achieved')
                ->get();

            foreach ($entries as $entry) {
                $current = $historicalHighest[$entry->user_id] ?? 0;
                if ($entry->tier_achieved > $current) {
                    $historicalHighest[$entry->user_id] = $entry->tier_achieved;
                }
            }
        }

        $results = collect();

        foreach ($affiliates as $affiliate) {
            $result = $commByUser[$affiliate->id] ?? null;
            if ($result === null) {
                continue;
            }

            // Get current_rank from qualification_snapshot
            $currentRank = $this->getCurrentRank($result, $tiers);

            if ($currentRank === null || $currentRank <= 0) {
                continue;
            }

            $previousHighest = $historicalHighest[$affiliate->id] ?? 0;

            if ($currentRank <= $previousHighest) {
                continue;
            }

            // Award the bonus for the HIGHEST new tier achieved
            $tier = $tiersByLevel[$currentRank] ?? null;
            if ($tier === null) {
                continue;
            }

            $bonusAmount = (string) $tier->amount;

            if (bccomp($bonusAmount, '0', 4) <= 0) {
                continue;
            }

            $results->push(new BonusResult(
                user_id: $affiliate->id,
                amount: $bonusAmount,
                bonus_type_id: $bonusType->id,
                tier_achieved: $currentRank,
                qualification_snapshot: [
                    'bonus_type' => 'rank_advancement',
                    'previous_highest_rank' => $previousHighest,
                    'new_rank' => $currentRank,
                    'tier_label' => $tier->label,
                    'one_time_amount' => $bonusAmount,
                ],
                description: sprintf(
                    'Rank advancement bonus: rank %d -> %d (%s), one-time $%s',
                    $previousHighest,
                    $currentRank,
                    $tier->label ?? "Level {$currentRank}",
                    $bonusAmount,
                ),
            ));
        }

        return $results;
    }

    /**
     * Determine the current rank from the commission result.
     *
     * First checks qualification_snapshot.current_rank (direct rank value).
     * If not present, falls back to matching against tier qualifier_type/qualifier_value.
     */
    private function getCurrentRank(array $result, Collection $tiers): ?int
    {
        // Direct rank from qualification snapshot
        $snapshot = $result['qualification_snapshot'] ?? [];
        $currentRank = $snapshot['current_rank'] ?? null;

        if ($currentRank !== null && $currentRank > 0) {
            return (int) $currentRank;
        }

        // Fallback: match against tier qualifiers
        $matchedLevel = null;
        foreach ($tiers as $tier) {
            $qualifies = match ($tier->qualifier_type) {
                'rank' => $this->meetsRankQualifier($result, $tier),
                'affiliate_tier' => $this->meetsAffiliateTierQualifier($result, $tier),
                'viral_tier' => $this->meetsViralTierQualifier($result, $tier),
                'active_customers' => $this->meetsActiveCustomersQualifier($result, $tier),
                'referred_volume' => $this->meetsReferredVolumeQualifier($result, $tier),
                default => false,
            };

            if ($qualifies) {
                $matchedLevel = $tier->level;
            }
        }

        return $matchedLevel;
    }

    private function meetsRankQualifier(array $result, $tier): bool
    {
        $snapshot = $result['qualification_snapshot'] ?? [];
        $currentRank = $snapshot['current_rank'] ?? null;

        if ($currentRank === null) {
            return false;
        }

        return (int) $currentRank >= (int) $tier->qualifier_value;
    }

    private function meetsAffiliateTierQualifier(array $result, $tier): bool
    {
        $affiliateTierIndex = $result['affiliate_tier_index'] ?? null;
        if ($affiliateTierIndex === null) {
            return false;
        }

        return ($affiliateTierIndex + 1) >= (int) $tier->qualifier_value;
    }

    private function meetsViralTierQualifier(array $result, $tier): bool
    {
        $viralTier = $result['viral_tier'] ?? null;
        if ($viralTier === null) {
            return false;
        }

        return $viralTier >= (int) $tier->qualifier_value;
    }

    private function meetsActiveCustomersQualifier(array $result, $tier): bool
    {
        $customerCount = $result['qualification_snapshot']['active_customer_count'] ?? 0;

        return $customerCount >= (int) $tier->qualifier_value;
    }

    private function meetsReferredVolumeQualifier(array $result, $tier): bool
    {
        $volume = $result['qualification_snapshot']['referred_volume_30d'] ?? '0';

        return bccomp((string) $volume, (string) $tier->qualifier_value, 4) >= 0;
    }
}
