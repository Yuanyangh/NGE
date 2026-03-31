<?php

namespace App\Services\Simulator;

use App\DTOs\SimulationConfig;
use Illuminate\Support\Collection;

class NetworkGrowthProjector
{
    private int $nextUserId = 1;

    /**
     * Index mapping user_id => collection index for O(1) lookups.
     * @var array<int, int>
     */
    private array $networkIndex = [];

    /**
     * Initialize the synthetic network from starting parameters.
     *
     * Returns a Collection of synthetic user arrays:
     * [
     *   'id' => int,
     *   'role' => 'affiliate'|'customer',
     *   'status' => 'active'|'inactive',
     *   'sponsor_id' => ?int,  // ID of the sponsoring affiliate
     *   'legs' => [],           // Direct referral IDs (for affiliates)
     *   'is_smartship' => bool, // For customers
     *   'enrolled_day' => int,
     * ]
     */
    public function initializeNetwork(SimulationConfig $config): Collection
    {
        $this->nextUserId = 1;
        $this->networkIndex = [];
        $network = collect();

        // Create starting affiliates (form a chain for tree structure)
        $affiliateIds = [];
        for ($i = 0; $i < $config->starting_affiliates; $i++) {
            $sponsorId = null;
            if ($i > 0) {
                // Distribute across existing affiliates respecting leg balance
                $sponsorId = $this->pickSponsor($affiliateIds, $network, $config);
            }

            $user = $this->createSyntheticUser('affiliate', $sponsorId, 0);
            $network->push($user);
            $this->networkIndex[$user['id']] = $network->count() - 1;
            $affiliateIds[] = $user['id'];

            if ($sponsorId !== null) {
                $this->addToSponsorLegs($network, $sponsorId, $user['id']);
            }
        }

        // Create starting customers distributed across affiliates
        for ($i = 0; $i < $config->starting_customers; $i++) {
            $sponsorId = $affiliateIds[array_rand($affiliateIds)];
            $user = $this->createSyntheticUser('customer', $sponsorId, 0);
            $user['is_smartship'] = (mt_rand(1, 100) / 100.0) <= $config->smartship_adoption_rate;
            $network->push($user);
            $this->networkIndex[$user['id']] = $network->count() - 1;

            $this->addToSponsorLegs($network, $sponsorId, $user['id']);
        }

        return $network;
    }

    /**
     * Project one day of network growth.
     */
    public function projectDay(Collection $network, SimulationConfig $config, int $day): Collection
    {
        $dailyChurnCustomer = $config->customer_monthly_churn_rate / 30.0;
        $dailyChurnAffiliate = $config->affiliate_monthly_churn_rate / 30.0;

        // Apply churn
        $network->transform(function (array $user) use ($dailyChurnCustomer, $dailyChurnAffiliate) {
            if ($user['status'] !== 'active') {
                return $user;
            }

            $churnRate = $user['role'] === 'affiliate' ? $dailyChurnAffiliate : $dailyChurnCustomer;
            if ((mt_rand(1, 10000) / 10000.0) < $churnRate) {
                $user['status'] = 'inactive';
            }

            return $user;
        });

        $activeAffiliates = $network->where('role', 'affiliate')->where('status', 'active');
        $activeAffiliateIds = $activeAffiliates->pluck('id')->toArray();

        if (empty($activeAffiliateIds)) {
            return $network;
        }

        // Add new affiliates
        $newAffiliateCount = $this->getGrowthCount($config->new_affiliates_per_day, $config->growth_curve, $day);
        for ($i = 0; $i < $newAffiliateCount; $i++) {
            $sponsorId = $this->pickSponsor($activeAffiliateIds, $network, $config);
            $user = $this->createSyntheticUser('affiliate', $sponsorId, $day);
            $network->push($user);
            $this->networkIndex[$user['id']] = $network->count() - 1;
            $activeAffiliateIds[] = $user['id'];
            $this->addToSponsorLegs($network, $sponsorId, $user['id']);
        }

        // Add new customers per active affiliate
        $dailyNewCustomerRate = $config->new_customers_per_affiliate_per_month / 30.0;
        $totalNewCustomers = (int) round($dailyNewCustomerRate * count($activeAffiliateIds));

        for ($i = 0; $i < $totalNewCustomers; $i++) {
            $sponsorId = $activeAffiliateIds[array_rand($activeAffiliateIds)];

            // Some new customers convert to affiliates
            $role = (mt_rand(1, 100) / 100.0) <= $config->affiliate_to_customer_ratio
                ? 'affiliate'
                : 'customer';

            $user = $this->createSyntheticUser($role, $sponsorId, $day);
            if ($role === 'customer') {
                $user['is_smartship'] = (mt_rand(1, 100) / 100.0) <= $config->smartship_adoption_rate;
            }
            $network->push($user);
            $this->networkIndex[$user['id']] = $network->count() - 1;

            if ($role === 'affiliate') {
                $activeAffiliateIds[] = $user['id'];
            }

            $this->addToSponsorLegs($network, $sponsorId, $user['id']);
        }

        return $network;
    }

    private function createSyntheticUser(string $role, ?int $sponsorId, int $day): array
    {
        return [
            'id' => $this->nextUserId++,
            'role' => $role,
            'status' => 'active',
            'sponsor_id' => $sponsorId,
            'legs' => [],
            'is_smartship' => false,
            'enrolled_day' => $day,
        ];
    }

    private function pickSponsor(array $affiliateIds, Collection $network, SimulationConfig $config): int
    {
        if (count($affiliateIds) <= 1) {
            return $affiliateIds[0];
        }

        // Weight sponsors: prefer affiliates with fewer legs (to balance the tree)
        // Unless leg_balance_ratio is low (concentrate on fewer sponsors)
        $weights = [];
        foreach ($affiliateIds as $id) {
            $user = isset($this->networkIndex[$id]) ? $network[$this->networkIndex[$id]] : null;
            if ($user === null || $user['status'] !== 'active') {
                continue;
            }
            $legCount = count($user['legs'] ?? []);
            $maxLegs = $config->average_legs_per_affiliate;

            // Higher balance ratio = more evenly distributed
            // Lower balance ratio = more concentrated (mega-legs)
            if ($config->leg_balance_ratio >= 0.5) {
                // Prefer sponsors with fewer legs
                $weight = max(1, $maxLegs - $legCount + 1);
            } else {
                // Prefer sponsors who already have legs (concentrate)
                $weight = max(1, $legCount + 1);
            }

            // Depth bias (use projection_days as scale factor)
            $scale = max(1, $config->projection_days);
            if ($config->depth_bias === 'deep') {
                // Prefer newer affiliates (deeper in tree)
                $weight *= max(1, $user['enrolled_day'] + 1);
            } elseif ($config->depth_bias === 'shallow') {
                // Prefer older affiliates (shallower)
                $weight *= max(1, $scale + 1 - $user['enrolled_day']);
            }

            $weights[$id] = $weight;
        }

        if (empty($weights)) {
            return $affiliateIds[array_rand($affiliateIds)];
        }

        return $this->weightedRandom($weights);
    }

    private function weightedRandom(array $weights): int
    {
        $totalWeight = array_sum($weights);
        $random = mt_rand(1, (int) max(1, $totalWeight));
        $cumulative = 0;

        foreach ($weights as $id => $weight) {
            $cumulative += $weight;
            if ($random <= $cumulative) {
                return $id;
            }
        }

        return array_key_first($weights);
    }

    private function addToSponsorLegs(Collection $network, int $sponsorId, int $childId): void
    {
        $idx = $this->networkIndex[$sponsorId] ?? null;
        if ($idx !== null) {
            $user = $network[$idx];
            $user['legs'][] = $childId;
            $network[$idx] = $user;
        }
    }

    private function getGrowthCount(float $baseRate, string $curve, int $day): int
    {
        $rate = match ($curve) {
            'exponential' => $baseRate * (1 + ($day / 365.0)),
            'logarithmic' => $baseRate * (1 + log(max(1, $day)) / 10.0),
            default => $baseRate, // linear
        };

        $whole = (int) floor($rate);
        $fractional = $rate - $whole;

        return $whole + ((mt_rand(1, 10000) / 10000.0) < $fractional ? 1 : 0);
    }
}
