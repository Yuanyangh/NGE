<?php

namespace App\Services\Commission;

use App\DTOs\PlanConfig;
use App\DTOs\VolumeSnapshot;

class QvvCalculator
{
    /**
     * Implements the QVV algorithm from Section 6.5.
     *
     * @param array $legVolumes Array of ['leg_root_user_id' => int, 'volume' => string]
     */
    public function calculate(array $legVolumes, PlanConfig $config): VolumeSnapshot
    {
        if (empty($legVolumes)) {
            return new VolumeSnapshot(
                total_viral_volume: '0',
                large_leg_volume: '0',
                small_legs_total: '0',
                benchmark: '0',
                capped_large_leg: '0',
                qualifying_viral_volume: '0',
                was_capped: false,
                leg_details: [],
            );
        }

        // Step 1: Sum all leg volumes (total viral volume)
        $totalViralVolume = '0';
        foreach ($legVolumes as $leg) {
            $totalViralVolume = bcadd($totalViralVolume, $leg['volume'], 4);
        }

        // Step 2: Identify Large Leg (L) = leg with highest volume
        $largeLegIndex = 0;
        $largeLegVolume = $legVolumes[0]['volume'];

        foreach ($legVolumes as $index => $leg) {
            if (bccomp($leg['volume'], $largeLegVolume, 4) > 0) {
                $largeLegVolume = $leg['volume'];
                $largeLegIndex = $index;
            }
        }

        // Step 3: Sum all Small Legs (Y = sum of all except L)
        $smallLegsTotal = '0';
        foreach ($legVolumes as $index => $leg) {
            if ($index !== $largeLegIndex) {
                $smallLegsTotal = bcadd($smallLegsTotal, $leg['volume'], 4);
            }
        }

        // Step 4: Compute benchmark: X = (2/3) * Y
        $benchmark = bcdiv(bcmul('2', $smallLegsTotal, 4), '3', 4);

        // Step 5-6: Apply cap
        $wasCapped = false;
        if (bccomp($benchmark, $largeLegVolume, 4) >= 0) {
            // X >= L: no cap needed, use L as-is
            $cappedLargeLeg = $largeLegVolume;
        } else {
            // X < L: cap L to X
            $cappedLargeLeg = $benchmark;
            $wasCapped = true;
        }

        // Step 7: QVV = capped_L + Y
        $qvv = bcadd($cappedLargeLeg, $smallLegsTotal, 4);

        $legDetails = array_map(fn ($leg, $i) => [
            'leg_root_user_id' => $leg['leg_root_user_id'],
            'volume' => $leg['volume'],
            'is_large_leg' => $i === $largeLegIndex,
        ], $legVolumes, array_keys($legVolumes));

        return new VolumeSnapshot(
            total_viral_volume: $totalViralVolume,
            large_leg_volume: $largeLegVolume,
            small_legs_total: $smallLegsTotal,
            benchmark: $benchmark,
            capped_large_leg: $cappedLargeLeg,
            qualifying_viral_volume: $qvv,
            was_capped: $wasCapped,
            leg_details: $legDetails,
        );
    }
}
