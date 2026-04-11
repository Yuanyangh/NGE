<?php

namespace Tests\Feature\Commission;

use App\Models\User;
use App\Scopes\CompanyScope;
use App\Services\Commission\Bonus\FastStartBonusCalculator;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Tests for FastStartBonusCalculator.
 *
 * Fast Start Bonus multiplies an affiliate's commission by a configured rate
 * when the commission run date falls within the affiliate's fast-start window
 * (enrolled_at + duration_days, inclusive boundary).
 *
 * The bonus is computed per affiliate. For each qualifying affiliate the
 * calculator returns an entry with the enhancement delta amount (not the full
 * commission — just the bonus on top: commission * (rate - 1)).
 *
 * These tests are TDD-first. FastStartBonusCalculator does not yet exist.
 */
class FastStartBonusCalculatorTest extends BonusTestCase
{
    private FastStartBonusCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createCompanyWithPlan();
        $this->calculator = new FastStartBonusCalculator();
    }

    /**
     * @test
     *
     * Affiliate enrolled 15 days ago. Fast start window = 30 days. Rate = 2.0.
     * Base commission = $50. Enhancement = 50 * (2.0 - 1.0) = $50.
     * Affiliate's total effective commission becomes $100 ($50 base + $50 bonus).
     */
    public function within_fast_start_window_commission_enhanced(): void
    {
        // Enrolled 15 days ago — within a 30-day window
        $enrolledAt = $this->today->copy()->subDays(15);

        $affiliate = User::factory()->affiliate()->create([
            'company_id'  => $this->company->id,
            'name'        => 'Fast Start Affiliate',
            'enrolled_at' => $enrolledAt,
        ]);

        \App\Models\GenealogyNode::factory()->create([
            'company_id' => $this->company->id,
            'user_id'    => $affiliate->id,
            'sponsor_id' => null,
            'tree_depth' => 0,
        ]);

        $bonusType = $this->createBonusType([
            'type'      => 'fast_start',
            'name'      => 'Fast Start Bonus',
            'is_active' => true,
        ]);
        // duration_days = 30, multiplier_rate = 2.0
        $this->createBonusConfig($bonusType, 'duration_days', '30');
        $this->createBonusConfig($bonusType, 'multiplier_rate', '2.0');

        $commissionResults = collect([
            $this->makeCommissionResult([
                'user_id'              => $affiliate->id,
                'affiliate_commission' => '50.0000',
                'viral_commission'     => '0',
            ]),
        ]);

        $affiliates = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->company->id)
            ->where('role', 'affiliate')
            ->get();

        $results = $this->calculator->calculate($bonusType, $affiliates, $commissionResults, $this->today);

        $entry = $results->firstWhere('user_id', $affiliate->id);
        $this->assertNotNull($entry, 'Affiliate within window should receive a fast start bonus entry');
        // Bonus delta = 50 * (2.0 - 1.0) = 50.00
        $this->assertBonusEquals('50.0000', (string) $entry->amount);
    }

    /**
     * @test
     *
     * Affiliate enrolled 45 days ago. Fast start window = 30 days.
     * Window has expired — no enhancement should be applied.
     */
    public function outside_fast_start_window_no_enhancement(): void
    {
        $enrolledAt = $this->today->copy()->subDays(45);

        $affiliate = User::factory()->affiliate()->create([
            'company_id'  => $this->company->id,
            'name'        => 'Expired Fast Start Affiliate',
            'enrolled_at' => $enrolledAt,
        ]);

        \App\Models\GenealogyNode::factory()->create([
            'company_id' => $this->company->id,
            'user_id'    => $affiliate->id,
            'sponsor_id' => null,
            'tree_depth' => 0,
        ]);

        $bonusType = $this->createBonusType([
            'type'      => 'fast_start',
            'is_active' => true,
        ]);
        $this->createBonusConfig($bonusType, 'duration_days', '30');
        $this->createBonusConfig($bonusType, 'multiplier_rate', '2.0');

        $commissionResults = collect([
            $this->makeCommissionResult([
                'user_id'              => $affiliate->id,
                'affiliate_commission' => '50.0000',
            ]),
        ]);

        $affiliates = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->company->id)
            ->where('role', 'affiliate')
            ->get();

        $results = $this->calculator->calculate($bonusType, $affiliates, $commissionResults, $this->today);

        $entry = $results->firstWhere('user_id', $affiliate->id);
        // Either no entry, or a $0 entry — both are valid
        if ($entry !== null) {
            $this->assertBonusEquals('0.0000', (string) $entry->amount);
        } else {
            $this->assertTrue(true, 'No entry for expired window is valid');
        }
    }

    /**
     * @test
     *
     * Boundary condition: affiliate enrolled exactly 30 days ago.
     * The fast start window is 30 days inclusive (enrolled_at + 29 days = today still qualifies
     * when duration = 30, meaning day 1 = enrolled_at, day 30 = enrolled_at + 29 days).
     * Today is exactly the 30th day — should still qualify.
     */
    public function exactly_on_boundary_still_qualifies(): void
    {
        // enrolled_at + (duration_days - 1) = today  →  enrolled_at = today - 29 days
        $enrolledAt = $this->today->copy()->subDays(29);

        $affiliate = User::factory()->affiliate()->create([
            'company_id'  => $this->company->id,
            'name'        => 'Boundary Affiliate',
            'enrolled_at' => $enrolledAt,
        ]);

        \App\Models\GenealogyNode::factory()->create([
            'company_id' => $this->company->id,
            'user_id'    => $affiliate->id,
            'sponsor_id' => null,
            'tree_depth' => 0,
        ]);

        $bonusType = $this->createBonusType([
            'type'      => 'fast_start',
            'is_active' => true,
        ]);
        $this->createBonusConfig($bonusType, 'duration_days', '30');
        $this->createBonusConfig($bonusType, 'multiplier_rate', '2.0');

        $commissionResults = collect([
            $this->makeCommissionResult([
                'user_id'              => $affiliate->id,
                'affiliate_commission' => '100.0000',
            ]),
        ]);

        $affiliates = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->company->id)
            ->where('role', 'affiliate')
            ->get();

        $results = $this->calculator->calculate($bonusType, $affiliates, $commissionResults, $this->today);

        $entry = $results->firstWhere('user_id', $affiliate->id);
        $this->assertNotNull($entry, 'Affiliate on boundary day 30 (inclusive) should qualify');
        // Bonus delta = 100 * (2.0 - 1.0) = 100.00
        $this->assertBonusEquals('100.0000', (string) $entry->amount);
    }

    /**
     * @test
     *
     * The fast start bonus applies only to the affiliate's own commission
     * (affiliate_commission), NOT to the viral_commission.
     * Only the affiliate commission portion should be enhanced.
     */
    public function applies_to_affiliate_only_viral_not_enhanced(): void
    {
        $enrolledAt = $this->today->copy()->subDays(10);

        $affiliate = User::factory()->affiliate()->create([
            'company_id'  => $this->company->id,
            'name'        => 'Affiliate Only Enhancement',
            'enrolled_at' => $enrolledAt,
        ]);

        \App\Models\GenealogyNode::factory()->create([
            'company_id' => $this->company->id,
            'user_id'    => $affiliate->id,
            'sponsor_id' => null,
            'tree_depth' => 0,
        ]);

        $bonusType = $this->createBonusType([
            'type'      => 'fast_start',
            'is_active' => true,
        ]);
        $this->createBonusConfig($bonusType, 'duration_days', '30');
        $this->createBonusConfig($bonusType, 'multiplier_rate', '2.0');
        // Explicitly configure: apply to affiliate commission only
        $this->createBonusConfig($bonusType, 'applies_to', 'affiliate_only');

        $commissionResults = collect([
            $this->makeCommissionResult([
                'user_id'              => $affiliate->id,
                'affiliate_commission' => '50.0000',
                'viral_commission'     => '20.0000',
            ]),
        ]);

        $affiliates = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->company->id)
            ->where('role', 'affiliate')
            ->get();

        $results = $this->calculator->calculate($bonusType, $affiliates, $commissionResults, $this->today);

        $entry = $results->firstWhere('user_id', $affiliate->id);
        $this->assertNotNull($entry);
        // Only affiliate portion enhanced: 50 * (2.0 - 1.0) = 50.00  (viral not included)
        $this->assertBonusEquals('50.0000', (string) $entry->amount);
    }

    /**
     * @test
     *
     * When bonus is configured with applies_to = 'both', both affiliate_commission
     * and viral_commission are enhanced.
     * affiliate=50, viral=20, rate=2.0 → delta = (50+20)*(2-1) = 70.
     */
    public function applies_to_both_enhanced(): void
    {
        $enrolledAt = $this->today->copy()->subDays(5);

        $affiliate = User::factory()->affiliate()->create([
            'company_id'  => $this->company->id,
            'name'        => 'Both Enhancement Affiliate',
            'enrolled_at' => $enrolledAt,
        ]);

        \App\Models\GenealogyNode::factory()->create([
            'company_id' => $this->company->id,
            'user_id'    => $affiliate->id,
            'sponsor_id' => null,
            'tree_depth' => 0,
        ]);

        $bonusType = $this->createBonusType([
            'type'      => 'fast_start',
            'is_active' => true,
        ]);
        $this->createBonusConfig($bonusType, 'duration_days', '30');
        $this->createBonusConfig($bonusType, 'multiplier_rate', '2.0');
        $this->createBonusConfig($bonusType, 'applies_to', 'both');

        $commissionResults = collect([
            $this->makeCommissionResult([
                'user_id'              => $affiliate->id,
                'affiliate_commission' => '50.0000',
                'viral_commission'     => '20.0000',
            ]),
        ]);

        $affiliates = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->company->id)
            ->where('role', 'affiliate')
            ->get();

        $results = $this->calculator->calculate($bonusType, $affiliates, $commissionResults, $this->today);

        $entry = $results->firstWhere('user_id', $affiliate->id);
        $this->assertNotNull($entry);
        // Both enhanced: (50 + 20) * (2.0 - 1.0) = 70.00
        $this->assertBonusEquals('70.0000', (string) $entry->amount);
    }
}
