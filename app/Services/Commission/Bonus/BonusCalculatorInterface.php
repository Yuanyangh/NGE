<?php

namespace App\Services\Commission\Bonus;

use App\Models\BonusType;
use Carbon\Carbon;
use Illuminate\Support\Collection;

interface BonusCalculatorInterface
{
    /**
     * Calculate bonus amounts for all qualifying affiliates.
     *
     * @param  BonusType  $bonusType  The bonus type with configs and tiers loaded
     * @param  Collection  $affiliates  Collection of User models (affiliates for this company)
     * @param  Collection  $commissionResults  Collection of commission result arrays from base calc
     * @param  Carbon  $date  The calculation date
     * @return Collection  Collection<BonusResult>
     */
    public function calculate(
        BonusType $bonusType,
        Collection $affiliates,
        Collection $commissionResults,
        Carbon $date,
    ): Collection;
}
