<?php

namespace Tests\Feature\Compliance;

use App\Models\CompanySetting;
use App\Services\Compliance\ChurnDetector;
use Carbon\Carbon;
use Tests\Feature\Commission\CommissionTestCase;

class ChurnDetectorTest extends CommissionTestCase
{
    private ChurnDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new ChurnDetector();
    }

    /** @test */
    public function detects_at_risk_affiliate(): void
    {
        $this->createCompanyWithPlan();

        // Default at_risk threshold = 30 days, inactive threshold = 60 days.
        // Last order 35 days ago → between 30 and 60 → 'at_risk'
        $affiliate = $this->createAffiliate('AtRisk');
        $this->createTransaction($affiliate, 50, null, 35, 'purchase', 'confirmed', true);

        $results = $this->detector->scan($this->company->id, $this->today);

        $this->assertCount(1, $results);
        $result = $results->first();
        $this->assertSame($affiliate->id, $result->user_id);
        $this->assertSame('at_risk', $result->risk_level);
        $this->assertSame(35, $result->days_since_last_order);
    }

    /** @test */
    public function detects_inactive_warning(): void
    {
        $this->createCompanyWithPlan();

        // Last order 65 days ago → >= 60 → 'inactive_warning'
        $affiliate = $this->createAffiliate('Inactive');
        $this->createTransaction($affiliate, 50, null, 65, 'purchase', 'confirmed', true);

        $results = $this->detector->scan($this->company->id, $this->today);

        $this->assertCount(1, $results);
        $result = $results->first();
        $this->assertSame($affiliate->id, $result->user_id);
        $this->assertSame('inactive_warning', $result->risk_level);
        $this->assertSame(65, $result->days_since_last_order);
    }

    /** @test */
    public function detects_declining_volume(): void
    {
        $this->createCompanyWithPlan();

        // Default volume decline threshold = 50%.
        // Current 30-day window: today - 29 days to today.
        // Previous 30-day window: today - 59 days to today - 30 days.
        // Previous vol = 100 XP, current vol = 40 XP → decline = 60% > 50% → 'declining'
        $affiliate = $this->createAffiliate('Declining');

        // Previous window: 35 days ago (falls in previous 30d window: 30–59 days ago)
        $this->createTransaction($affiliate, 100, null, 45, 'purchase', 'confirmed', true);

        // Current window: 5 days ago (falls in current 30d window: 0–29 days ago)
        $this->createTransaction($affiliate, 40, null, 5, 'purchase', 'confirmed', true);

        $results = $this->detector->scan($this->company->id, $this->today);

        $declining = $results->first(fn ($r) => $r->risk_level === 'declining');
        $this->assertNotNull($declining, 'Expected a declining risk result');
        $this->assertSame($affiliate->id, $declining->user_id);

        // Decline percent should be > 50 (specifically 60%)
        $this->assertGreaterThan(
            0,
            bccomp($declining->volume_change_pct !== null ? ltrim($declining->volume_change_pct, '-') : '0', '50', 2),
            "Expected volume decline > 50%, got {$declining->volume_change_pct}"
        );
    }

    /** @test */
    public function detects_stagnant_leader(): void
    {
        $this->createCompanyWithPlan();

        // Default stagnant_leader threshold = 60 days.
        // Leader has a recent personal order (not at_risk/inactive).
        // Their downline's last order was 65 days ago → stagnant_leader.
        $leader   = $this->createAffiliate('Leader');
        $downline = $this->createAffiliate('Downline', $this->nodeFor($leader));

        // Leader has a recent order (within 30 days)
        $this->createTransaction($leader, 50, null, 5, 'purchase', 'confirmed', true);

        // Downline's last order was 65 days ago
        $this->createTransaction($downline, 50, null, 65, 'purchase', 'confirmed', true);

        $results = $this->detector->scan($this->company->id, $this->today);

        $stagnant = $results->first(fn ($r) => $r->risk_level === 'stagnant_leader');
        $this->assertNotNull($stagnant, 'Expected a stagnant_leader result for the leader');
        $this->assertSame($leader->id, $stagnant->user_id);
    }

    /** @test */
    public function active_affiliate_not_flagged(): void
    {
        $this->createCompanyWithPlan();

        // Recent order 5 days ago → well within all thresholds → should NOT appear
        $affiliate = $this->createAffiliate('Active');
        $this->createTransaction($affiliate, 50, null, 5, 'purchase', 'confirmed', true);

        $results = $this->detector->scan($this->company->id, $this->today);

        $found = $results->first(fn ($r) => $r->user_id === $affiliate->id);
        $this->assertNull($found, 'Active affiliate with recent order must not be flagged');
    }

    /** @test */
    public function respects_custom_thresholds(): void
    {
        $this->createCompanyWithPlan();

        // Override at_risk threshold to 10 days
        CompanySetting::withoutGlobalScopes()->updateOrInsert(
            ['company_id' => $this->company->id, 'key' => 'churn_at_risk_days'],
            ['value' => '10'],
        );
        // Override inactive threshold to 20 days
        CompanySetting::withoutGlobalScopes()->updateOrInsert(
            ['company_id' => $this->company->id, 'key' => 'churn_inactive_days'],
            ['value' => '20'],
        );

        $affiliate = $this->createAffiliate('CustomThreshold');

        // Last order 15 days ago → between 10 and 20 → 'at_risk' under custom thresholds
        // (with defaults this would not be flagged: default at_risk=30)
        $this->createTransaction($affiliate, 50, null, 15, 'purchase', 'confirmed', true);

        $results = $this->detector->scan($this->company->id, $this->today);

        $this->assertCount(1, $results);
        $this->assertSame('at_risk', $results->first()->risk_level);
        $this->assertSame(15, $results->first()->days_since_last_order);
    }
}
