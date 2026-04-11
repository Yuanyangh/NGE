<?php

namespace Tests\Feature\Commission;

use App\Models\User;
use App\Scopes\CompanyScope;
use App\Services\Commission\Bonus\LeadershipBonusCalculator;
use Illuminate\Support\Collection;

/**
 * Tests for LeadershipBonusCalculator.
 *
 * Leadership Bonus pays a fixed amount per period to affiliates who are
 * actively maintaining a leadership rank (e.g. Manager, Director).
 *
 * The bonus is NOT cumulative: an affiliate at Director tier receives only the
 * Director amount ($1,500), not Manager ($500) + Director ($1,500).
 *
 * Tiers in bonus_type_tiers:
 *   level          = rank ordinal
 *   label          = rank name  (e.g. 'Manager', 'Director')
 *   qualifier_type = 'rank'
 *   qualifier_value= minimum rank level required to qualify for this tier
 *   amount         = flat periodic bonus for this exact rank
 *
 * The qualification_snapshot must include 'current_rank' indicating the
 * affiliate's maintained rank for the period.
 *
 * These tests are TDD-first. LeadershipBonusCalculator does not yet exist.
 */
class LeadershipBonusCalculatorTest extends BonusTestCase
{
    private LeadershipBonusCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createCompanyWithPlan();
        $this->calculator = new LeadershipBonusCalculator();
    }

    /**
     * @test
     *
     * Affiliate holds Director rank (level 2). Director bonus = $1,500.
     * Expected: affiliate receives exactly $1,500 for this period.
     */
    public function qualified_leader_receives_amount(): void
    {
        $affiliate = $this->createAffiliate('Director Affiliate');

        $bonusType = $this->createBonusType([
            'type'      => 'leadership',
            'name'      => 'Leadership Bonus',
            'is_active' => true,
        ]);
        $this->createBonusTier($bonusType, [
            'level'          => 1,
            'label'          => 'Manager',
            'qualifier_type' => 'rank',
            'qualifier_value'=> 1,
            'amount'         => '500.0000',
        ]);
        $this->createBonusTier($bonusType, [
            'level'          => 2,
            'label'          => 'Director',
            'qualifier_type' => 'rank',
            'qualifier_value'=> 2,
            'amount'         => '1500.0000',
        ]);

        // Affiliate is at Director rank (level 2)
        $commissionResults = collect([
            $this->makeCommissionResult([
                'user_id' => $affiliate->id,
                'qualification_snapshot' => [
                    'current_rank' => 2,
                ],
            ]),
        ]);

        $allAffiliates = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->company->id)
            ->where('role', 'affiliate')
            ->get();

        $results = $this->calculator->calculate($bonusType, $allAffiliates, $commissionResults, $this->today);

        $entry = $results->firstWhere('user_id', $affiliate->id);
        $this->assertNotNull($entry, 'Director-rank affiliate should receive a leadership bonus entry');
        $this->assertBonusEquals('1500.0000', (string) $entry->amount);
    }

    /**
     * @test
     *
     * Affiliate previously held Manager rank but has since dropped below the
     * qualification threshold. current_rank in the snapshot is null or 0.
     * Expected: no bonus paid.
     */
    public function rank_not_maintained_returns_zero(): void
    {
        $affiliate = $this->createAffiliate('Lapsed Leader');

        $bonusType = $this->createBonusType([
            'type'      => 'leadership',
            'is_active' => true,
        ]);
        $this->createBonusTier($bonusType, [
            'level'          => 1,
            'label'          => 'Manager',
            'qualifier_type' => 'rank',
            'qualifier_value'=> 1,
            'amount'         => '500.0000',
        ]);

        // Rank not maintained this period
        $commissionResults = collect([
            $this->makeCommissionResult([
                'user_id' => $affiliate->id,
                'qualification_snapshot' => [
                    'current_rank' => null,
                ],
            ]),
        ]);

        $allAffiliates = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->company->id)
            ->where('role', 'affiliate')
            ->get();

        $results = $this->calculator->calculate($bonusType, $allAffiliates, $commissionResults, $this->today);

        $entry = $results->firstWhere('user_id', $affiliate->id);
        if ($entry !== null) {
            $this->assertBonusEquals('0.0000', (string) $entry->amount,
                'Lapsed rank should produce $0 bonus');
        } else {
            $this->assertTrue(true, 'No entry for lapsed rank is correct');
        }
    }

    /**
     * @test
     *
     * Leadership bonus is NOT cumulative. An affiliate at Director (level 2)
     * receives ONLY the Director amount ($1,500), not Manager ($500) + Director
     * ($1,500) = $2,000. The calculator must apply the tier that exactly matches
     * the affiliate's current rank, not all tiers up to and including that rank.
     */
    public function not_cumulative(): void
    {
        $affiliate = $this->createAffiliate('Director Non-Cumulative');

        $bonusType = $this->createBonusType([
            'type'      => 'leadership',
            'is_active' => true,
        ]);
        $this->createBonusTier($bonusType, [
            'level'          => 1,
            'label'          => 'Manager',
            'qualifier_type' => 'rank',
            'qualifier_value'=> 1,
            'amount'         => '500.0000',
        ]);
        $this->createBonusTier($bonusType, [
            'level'          => 2,
            'label'          => 'Director',
            'qualifier_type' => 'rank',
            'qualifier_value'=> 2,
            'amount'         => '1500.0000',
        ]);

        // Affiliate is at Director rank (level 2)
        $commissionResults = collect([
            $this->makeCommissionResult([
                'user_id' => $affiliate->id,
                'qualification_snapshot' => [
                    'current_rank' => 2,
                ],
            ]),
        ]);

        $allAffiliates = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->company->id)
            ->where('role', 'affiliate')
            ->get();

        $results = $this->calculator->calculate($bonusType, $allAffiliates, $commissionResults, $this->today);

        // Sum all entries for this affiliate to verify non-cumulative behavior
        $affiliateEntries = $results->filter(fn ($e) => $e->user_id === $affiliate->id);
        $total = $affiliateEntries->reduce(
            fn (string $carry, $entry) => bcadd($carry, (string) $entry->amount, 4),
            '0'
        );

        // Must be $1,500, NOT $2,000
        $this->assertBonusEquals('1500.0000', $total,
            'Leadership bonus is not cumulative — Director gets $1,500 only, not Manager+Director=$2,000');
    }
}
