<?php

namespace App\Services\Commission\Bonus;

use App\DTOs\BonusResult;
use App\Models\BonusType;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FastStartBonusCalculator implements BonusCalculatorInterface
{
    /**
     * Calculate fast start bonus for recently enrolled affiliates.
     *
     * If an affiliate enrolled within `duration_days` of the current date,
     * they receive an enhanced rate on their base commissions. The bonus amount
     * is the EXTRA amount: (multiplier_rate - 1.0) * base_commission.
     *
     * Config keys:
     *   duration_days   - window size in days from enrollment
     *   multiplier_rate - the multiplier (e.g. 2.0 means 2x, bonus delta = 1x)
     *   applies_to      - 'affiliate_only' | 'viral_only' | 'both' (default: both)
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

        $configMap = $bonusType->configs()->pluck('value', 'key')->toArray();

        $durationDays = (int) ($configMap['duration_days'] ?? 30);
        $multiplierRate = $configMap['multiplier_rate'] ?? '1.5';
        $appliesTo = $configMap['applies_to'] ?? 'both';

        // The multiplier for the EXTRA amount is (multiplier_rate - 1.0)
        $extraMultiplier = bcsub($multiplierRate, '1', 4);

        if (bccomp($extraMultiplier, '0', 4) <= 0) {
            return collect();
        }

        // Index commission results by user_id
        $commByUser = [];
        foreach ($commissionResults as $result) {
            $commByUser[$result['user_id']] = $result;
        }

        // Cutoff: enrolled_at must be >= cutoffDate to qualify
        $cutoffDate = $date->copy()->subDays($durationDays);

        $results = collect();

        foreach ($affiliates as $affiliate) {
            if ($affiliate->enrolled_at === null) {
                continue;
            }

            // Enrolled before cutoff = outside window
            if ($affiliate->enrolled_at->lt($cutoffDate)) {
                continue;
            }

            $result = $commByUser[$affiliate->id] ?? null;
            if ($result === null) {
                continue;
            }

            // Determine base commission to enhance
            $baseAmount = match ($appliesTo) {
                'affiliate_only', 'affiliate' => $result['affiliate_commission'] ?? '0',
                'viral_only', 'viral' => $result['viral_commission'] ?? '0',
                default => bcadd(
                    $result['affiliate_commission'] ?? '0',
                    $result['viral_commission'] ?? '0',
                    4,
                ),
            };

            if (bccomp($baseAmount, '0', 4) <= 0) {
                continue;
            }

            $bonusAmount = bcmul($baseAmount, $extraMultiplier, 4);

            if (bccomp($bonusAmount, '0', 4) > 0) {
                $daysSinceEnrollment = (int) $affiliate->enrolled_at->diffInDays($date);

                $results->push(new BonusResult(
                    user_id: $affiliate->id,
                    amount: $bonusAmount,
                    bonus_type_id: $bonusType->id,
                    tier_achieved: null,
                    qualification_snapshot: [
                        'bonus_type' => 'fast_start',
                        'enrolled_at' => $affiliate->enrolled_at->toDateString(),
                        'days_since_enrollment' => $daysSinceEnrollment,
                        'duration_days' => $durationDays,
                        'multiplier_rate' => $multiplierRate,
                        'applies_to' => $appliesTo,
                        'base_amount' => $baseAmount,
                    ],
                    description: sprintf(
                        'Fast start bonus: %d days since enrollment, %.0f%% enhancement on $%s',
                        $daysSinceEnrollment,
                        (float) $extraMultiplier * 100,
                        $baseAmount,
                    ),
                ));
            }
        }

        return $results;
    }
}
