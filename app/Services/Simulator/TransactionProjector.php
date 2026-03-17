<?php

namespace App\Services\Simulator;

use App\DTOs\SimulationConfig;
use Illuminate\Support\Collection;

class TransactionProjector
{
    private int $nextTxnId = 1;

    /**
     * Generate synthetic transactions for a single day.
     *
     * Returns a Collection of synthetic transaction arrays:
     * [
     *   'id' => int,
     *   'user_id' => int,           // buyer
     *   'referred_by_user_id' => int, // sponsor affiliate
     *   'type' => 'purchase'|'smartship'|'refund',
     *   'xp' => string,
     *   'amount' => string,
     *   'status' => 'confirmed'|'reversed',
     *   'qualifies_for_commission' => bool,
     *   'transaction_date' => string, // Y-m-d
     *   'day' => int,
     * ]
     */
    public function projectDay(
        Collection $network,
        SimulationConfig $config,
        int $day,
        string $dateString,
    ): Collection {
        $transactions = collect();

        $activeCustomers = $network->where('role', 'customer')->where('status', 'active');
        $dailyOrderProbability = $config->orders_per_customer_per_month / 30.0;

        foreach ($activeCustomers as $customer) {
            // Regular order probability
            if ((mt_rand(1, 10000) / 10000.0) < $dailyOrderProbability) {
                $xp = $this->randomizeXp($config->average_order_xp);
                $transaction = $this->createTransaction(
                    userId: $customer['id'],
                    referredBy: $customer['sponsor_id'],
                    type: 'purchase',
                    xp: $xp,
                    dateString: $dateString,
                    day: $day,
                );

                // Apply refund rate
                if ((mt_rand(1, 10000) / 10000.0) < $config->refund_rate) {
                    $transaction['status'] = 'reversed';
                    $transaction['qualifies_for_commission'] = false;
                }

                $transactions->push($transaction);
            }

            // SmartShip: once per month, relative to enrollment date
            $daysSinceEnrollment = $day - ($customer['enrolled_day'] ?? 0);
            if ($customer['is_smartship'] && $daysSinceEnrollment > 0 && ($daysSinceEnrollment % 30 === 0)) {
                $xp = $this->randomizeXp($config->smartship_average_xp);
                $transactions->push($this->createTransaction(
                    userId: $customer['id'],
                    referredBy: $customer['sponsor_id'],
                    type: 'smartship',
                    xp: $xp,
                    dateString: $dateString,
                    day: $day,
                ));
            }
        }

        return $transactions;
    }

    private function createTransaction(
        int $userId,
        ?int $referredBy,
        string $type,
        string $xp,
        string $dateString,
        int $day,
    ): array {
        return [
            'id' => $this->nextTxnId++,
            'user_id' => $userId,
            'referred_by_user_id' => $referredBy,
            'type' => $type,
            'xp' => $xp,
            'amount' => $xp,
            'status' => 'confirmed',
            'qualifies_for_commission' => true,
            'transaction_date' => $dateString,
            'day' => $day,
        ];
    }

    /**
     * Add +/-20% randomness to XP value.
     */
    private function randomizeXp(float $baseXp): string
    {
        $variance = $baseXp * 0.2;
        $min = (int) round(($baseXp - $variance) * 100);
        $max = (int) round(($baseXp + $variance) * 100);
        $xp = mt_rand($min, $max) / 100.0;

        return bcmul((string) $xp, '1', 2);
    }
}
