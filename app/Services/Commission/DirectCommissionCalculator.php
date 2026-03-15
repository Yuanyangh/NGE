<?php

namespace App\Services\Commission;

use App\DTOs\PlanConfig;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

class DirectCommissionCalculator
{
    /**
     * Calculate the affiliate (direct) commission for a given affiliate on a given date.
     *
     * The tier is determined by rolling 30-day metrics.
     * The payout applies that tier rate to TODAY's new volume only.
     */
    public function calculate(
        User $affiliate,
        Carbon $date,
        PlanConfig $config,
        int $tierIndex,
        float $tierRate,
    ): string {
        $todayVolume = $this->getTodaysNewVolume($affiliate, $date, $config);

        return bcmul($todayVolume, (string) $tierRate, 4);
    }

    public function getTodaysNewVolume(User $affiliate, Carbon $date, PlanConfig $config): string
    {
        $query = Transaction::withoutGlobalScopes()
            ->where('referred_by_user_id', $affiliate->id)
            ->where('company_id', $affiliate->company_id)
            ->where('status', 'confirmed')
            ->where('qualifies_for_commission', true)
            ->whereDate('transaction_date', $date->toDateString());

        if (! $config->self_purchase_earns_commission) {
            $query->where('user_id', '!=', $affiliate->id);
        }

        $volume = $query->sum('xp');

        return (string) $volume;
    }
}
