<?php

namespace App\Services\Commission\Bonus;

use App\DTOs\BonusResult;
use App\Models\BonusType;
use App\Models\Transaction;
use App\Scopes\CompanyScope;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PoolSharingBonusCalculator implements BonusCalculatorInterface
{
    /**
     * Calculate pool sharing bonus.
     *
     * A percentage of the company volume forms a pool that is distributed among
     * qualifying affiliates. Distribution is either equal or volume-weighted.
     *
     * Config keys:
     *   pool_percent           - percentage of company volume for the pool (e.g. '0.05')
     *   distribution_method    - 'equal' | 'volume_weighted'
     *   company_volume_override - (optional) override for test isolation
     *   qualifying_min_rank    - (optional) minimum rank to qualify (default: 0 = all qualify)
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

        $poolPercent = $configMap['pool_percent'] ?? ($configMap['pool_percentage'] ?? '0.05');
        $distributionMethod = $configMap['distribution_method'] ?? 'equal';
        $companyVolumeOverride = $configMap['company_volume_override'] ?? null;
        $qualifyingMinRank = (int) ($configMap['qualifying_min_rank'] ?? 0);

        // Determine company volume
        if ($companyVolumeOverride !== null) {
            $companyVolume = (string) $companyVolumeOverride;
        } else {
            $companyId = $affiliates->first()?->company_id;
            if (! $companyId) {
                return collect();
            }

            $windowStart = $date->copy()->subDays(29);
            $companyVolume = (string) Transaction::withoutGlobalScope(CompanyScope::class)
                ->where('company_id', $companyId)
                ->where('status', 'confirmed')
                ->where('qualifies_for_commission', true)
                ->whereDate('transaction_date', '>=', $windowStart->toDateString())
                ->whereDate('transaction_date', '<=', $date->toDateString())
                ->sum('xp');
        }

        if (bccomp($companyVolume, '0', 4) <= 0) {
            return collect();
        }

        // Calculate pool
        $pool = bcmul($companyVolume, (string) $poolPercent, 4);

        if (bccomp($pool, '0', 4) <= 0) {
            return collect();
        }

        // Index commission results by user_id
        $commByUser = [];
        foreach ($commissionResults as $result) {
            $commByUser[$result['user_id']] = $result;
        }

        // Filter qualifying affiliates
        $qualifyingAffiliates = [];
        foreach ($affiliates as $affiliate) {
            $result = $commByUser[$affiliate->id] ?? null;
            if ($result === null) {
                continue;
            }

            if ($qualifyingMinRank > 0) {
                $rank = $this->determineRank($result);
                if ($rank < $qualifyingMinRank) {
                    continue;
                }
            }

            $qualifyingAffiliates[] = [
                'affiliate' => $affiliate,
                'result' => $result,
            ];
        }

        $qualifyingCount = count($qualifyingAffiliates);
        if ($qualifyingCount === 0) {
            return collect();
        }

        $results = collect();

        if ($distributionMethod === 'equal') {
            $shareAmount = bcdiv($pool, (string) $qualifyingCount, 4);

            foreach ($qualifyingAffiliates as $qa) {
                if (bccomp($shareAmount, '0', 4) > 0) {
                    $results->push(new BonusResult(
                        user_id: $qa['affiliate']->id,
                        amount: $shareAmount,
                        bonus_type_id: $bonusType->id,
                        tier_achieved: null,
                        qualification_snapshot: [
                            'bonus_type' => 'pool_sharing',
                            'distribution_method' => 'equal',
                            'pool' => $pool,
                            'qualifying_count' => $qualifyingCount,
                            'company_volume' => $companyVolume,
                        ],
                        description: sprintf(
                            'Pool sharing bonus: equal share $%s (%d qualifying affiliates)',
                            $shareAmount,
                            $qualifyingCount,
                        ),
                    ));
                }
            }
        } else {
            // Volume-weighted distribution
            $totalQualifyingVolume = '0';
            foreach ($qualifyingAffiliates as &$qa) {
                $volume = $qa['result']['qualification_snapshot']['referred_volume_30d'] ?? '0';
                $qa['volume'] = (string) $volume;
                $totalQualifyingVolume = bcadd($totalQualifyingVolume, (string) $volume, 4);
            }
            unset($qa);

            if (bccomp($totalQualifyingVolume, '0', 4) <= 0) {
                return collect();
            }

            foreach ($qualifyingAffiliates as $qa) {
                $weight = bcdiv($qa['volume'], $totalQualifyingVolume, 8);
                $shareAmount = bcmul($pool, $weight, 4);

                if (bccomp($shareAmount, '0', 4) > 0) {
                    $results->push(new BonusResult(
                        user_id: $qa['affiliate']->id,
                        amount: $shareAmount,
                        bonus_type_id: $bonusType->id,
                        tier_achieved: null,
                        qualification_snapshot: [
                            'bonus_type' => 'pool_sharing',
                            'distribution_method' => 'volume_weighted',
                            'pool' => $pool,
                            'affiliate_volume' => $qa['volume'],
                            'total_qualifying_volume' => $totalQualifyingVolume,
                            'weight' => $weight,
                            'company_volume' => $companyVolume,
                        ],
                        description: sprintf(
                            'Pool sharing bonus: volume-weighted $%s (weight: %s)',
                            $shareAmount,
                            $weight,
                        ),
                    ));
                }
            }
        }

        return $results;
    }

    private function determineRank(array $result): int
    {
        $snapshot = $result['qualification_snapshot'] ?? [];
        $currentRank = $snapshot['current_rank'] ?? null;
        if ($currentRank !== null && $currentRank > 0) {
            return (int) $currentRank;
        }

        $viralTier = $result['viral_tier'] ?? null;
        if ($viralTier !== null && $viralTier > 0) {
            return $viralTier;
        }

        $affiliateTierIndex = $result['affiliate_tier_index'] ?? null;
        if ($affiliateTierIndex !== null) {
            return $affiliateTierIndex + 1;
        }

        return 0;
    }
}
