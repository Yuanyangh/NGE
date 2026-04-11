<?php

namespace App\Enums;

enum BonusTypeEnum: string
{
    case Matching = 'matching';
    case FastStart = 'fast_start';
    case RankAdvancement = 'rank_advancement';
    case PoolSharing = 'pool_sharing';
    case Leadership = 'leadership';

    public function label(): string
    {
        return match($this) {
            self::Matching => 'Matching Bonus',
            self::FastStart => 'Fast Start Bonus',
            self::RankAdvancement => 'Rank Advancement Bonus',
            self::PoolSharing => 'Pool Sharing Bonus',
            self::Leadership => 'Leadership Bonus',
        };
    }
}
