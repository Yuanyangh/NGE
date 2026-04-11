<?php

namespace App\Services\Commission\Bonus;

use App\DTOs\BonusResult;
use App\Models\BonusType;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LeadershipBonusCalculator implements BonusCalculatorInterface
{
    /**
     * Calculate leadership bonus based on current rank.
     *
     * Each tier has a fixed amount for affiliates who maintain that rank.
     * Not cumulative: affiliate gets ONLY the amount for their highest matched tier.
     *
     * Tiers use qualifier_type='rank' with qualifier_value = minimum rank level.
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

        $results = collect();

        foreach ($affiliates as $affiliate) {
            $result = $commByUser[$affiliate->id] ?? null;
            if ($result === null) {
                continue;
            }

            // Find the highest matching tier for this affiliate
            $matchedTier = null;
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
                    $matchedTier = $tier;
                }
            }

            if ($matchedTier === null) {
                continue;
            }

            $amount = (string) $matchedTier->amount;

            if (bccomp($amount, '0', 4) <= 0) {
                continue;
            }

            $results->push(new BonusResult(
                user_id: $affiliate->id,
                amount: $amount,
                bonus_type_id: $bonusType->id,
                tier_achieved: $matchedTier->level,
                qualification_snapshot: [
                    'bonus_type' => 'leadership',
                    'tier_level' => $matchedTier->level,
                    'tier_label' => $matchedTier->label,
                    'amount' => $amount,
                    'qualifier_type' => $matchedTier->qualifier_type,
                    'qualifier_value' => (string) $matchedTier->qualifier_value,
                ],
                description: sprintf(
                    'Leadership bonus: %s (tier %d), $%s',
                    $matchedTier->label ?? "Level {$matchedTier->level}",
                    $matchedTier->level,
                    $amount,
                ),
            ));
        }

        return $results;
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
