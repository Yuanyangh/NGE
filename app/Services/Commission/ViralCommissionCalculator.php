<?php

namespace App\Services\Commission;

use App\DTOs\PlanConfig;
use App\DTOs\VolumeSnapshot;

class ViralCommissionCalculator
{
    /**
     * Calculate viral commission for an affiliate based on their QVV.
     *
     * Returns ['amount' => string, 'tier' => ?int, 'daily_reward' => ?float]
     */
    public function calculate(
        int $activeCustomerCount,
        string $referredVolume,
        VolumeSnapshot $volumeSnapshot,
        PlanConfig $config,
    ): array {
        $qvv = $volumeSnapshot->qualifying_viral_volume;

        $matchedTier = null;

        foreach ($config->viral_tiers as $index => $tier) {
            if ($activeCustomerCount >= $tier->min_active_customers
                && bccomp($referredVolume, (string) $tier->min_referred_volume, 4) >= 0
                && bccomp($qvv, (string) $tier->min_qvv, 4) >= 0) {
                $matchedTier = $index;
            }
        }

        if ($matchedTier === null) {
            return [
                'amount' => '0',
                'tier' => null,
                'daily_reward' => null,
            ];
        }

        $tier = $config->viral_tiers[$matchedTier];

        return [
            'amount' => (string) $tier->daily_reward,
            'tier' => $tier->tier,
            'daily_reward' => $tier->daily_reward,
        ];
    }
}
