<?php

namespace App\Services\Commission\Bonus;

use App\Enums\BonusTypeEnum;

class BonusDispatcher
{
    public function __construct(
        private readonly MatchingBonusCalculator $matchingCalculator,
        private readonly FastStartBonusCalculator $fastStartCalculator,
        private readonly RankAdvancementBonusCalculator $rankAdvancementCalculator,
        private readonly PoolSharingBonusCalculator $poolSharingCalculator,
        private readonly LeadershipBonusCalculator $leadershipCalculator,
    ) {}

    /**
     * Resolve the correct calculator service for a given bonus type.
     */
    public function getCalculator(BonusTypeEnum $type): BonusCalculatorInterface
    {
        return match ($type) {
            BonusTypeEnum::Matching => $this->matchingCalculator,
            BonusTypeEnum::FastStart => $this->fastStartCalculator,
            BonusTypeEnum::RankAdvancement => $this->rankAdvancementCalculator,
            BonusTypeEnum::PoolSharing => $this->poolSharingCalculator,
            BonusTypeEnum::Leadership => $this->leadershipCalculator,
        };
    }
}
