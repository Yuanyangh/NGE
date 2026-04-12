<?php

namespace Tests\Feature\Compliance;

use App\Models\CompanySetting;
use App\Services\Compliance\InventoryLoadingDetector;
use Carbon\Carbon;
use Tests\Feature\Commission\CommissionTestCase;

class InventoryLoadingDetectorTest extends CommissionTestCase
{
    private InventoryLoadingDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new InventoryLoadingDetector();
    }

    /** @test */
    public function flags_affiliate_above_threshold(): void
    {
        $this->createCompanyWithPlan();

        // Default threshold is 0.80. Affiliate with 90% personal purchases must be flagged.
        $affiliate = $this->createAffiliate('HighPersonal');
        $customer  = $this->createCustomer('Cust1', $this->nodeFor($affiliate));

        // Affiliate personal: 90 XP
        $this->createTransaction($affiliate, 90, null, 0, 'purchase', 'confirmed', true);
        // Referred volume from customer: 10 XP (so ratio = 90/100 = 0.90)
        $this->createTransaction($customer, 10, $affiliate, 0, 'purchase', 'confirmed', true);

        $results = $this->detector->scan($this->company->id, $this->today);

        $this->assertCount(1, $results);
        $flagged = $results->first();
        $this->assertSame($affiliate->id, $flagged->user_id);
        $this->assertSame('warning', $flagged->risk_level);

        // Ratio should be 0.90 (10 decimal places from bcdiv)
        $this->assertSame(0, bccomp('0.9000', $flagged->ratio, 4),
            "Expected ratio ~0.90, got {$flagged->ratio}");
    }

    /** @test */
    public function does_not_flag_affiliate_below_threshold(): void
    {
        $this->createCompanyWithPlan();

        // Default threshold 0.80. Affiliate with 50% personal → ratio = 0.50 → NOT flagged.
        $affiliate = $this->createAffiliate('LowPersonal');
        $customer  = $this->createCustomer('Cust2', $this->nodeFor($affiliate));

        // Affiliate personal: 50 XP
        $this->createTransaction($affiliate, 50, null, 0, 'purchase', 'confirmed', true);
        // Customer referred: 50 XP → ratio = 50/100 = 0.50
        $this->createTransaction($customer, 50, $affiliate, 0, 'purchase', 'confirmed', true);

        $results = $this->detector->scan($this->company->id, $this->today);

        $this->assertCount(0, $results);
    }

    /** @test */
    public function critical_risk_above_95_percent(): void
    {
        $this->createCompanyWithPlan();

        // Ratio of 96/100 = 0.96 → exceeds 0.95 → 'critical'
        $affiliate = $this->createAffiliate('CriticalPersonal');
        $customer  = $this->createCustomer('Cust3', $this->nodeFor($affiliate));

        $this->createTransaction($affiliate, 96, null, 0, 'purchase', 'confirmed', true);
        $this->createTransaction($customer, 4, $affiliate, 0, 'purchase', 'confirmed', true);

        $results = $this->detector->scan($this->company->id, $this->today);

        $this->assertCount(1, $results);
        $this->assertSame('critical', $results->first()->risk_level);
    }

    /** @test */
    public function skips_affiliate_with_no_activity(): void
    {
        $this->createCompanyWithPlan();

        // Affiliate exists but has zero transactions — must not appear in results
        $this->createAffiliate('Inactive');

        $results = $this->detector->scan($this->company->id, $this->today);

        $this->assertCount(0, $results);
    }

    /** @test */
    public function respects_custom_threshold(): void
    {
        $this->createCompanyWithPlan();

        // Lower threshold to 0.50: affiliate at 60% personal should now be flagged
        CompanySetting::withoutGlobalScopes()->updateOrInsert(
            ['company_id' => $this->company->id, 'key' => 'inventory_loading_threshold'],
            ['value' => '0.50'],
        );

        $affiliate = $this->createAffiliate('ModeratePersonal');
        $customer  = $this->createCustomer('Cust4', $this->nodeFor($affiliate));

        $this->createTransaction($affiliate, 60, null, 0, 'purchase', 'confirmed', true);
        $this->createTransaction($customer, 40, $affiliate, 0, 'purchase', 'confirmed', true);
        // ratio = 60/100 = 0.60 > 0.50 threshold → should be flagged

        $results = $this->detector->scan($this->company->id, $this->today);

        $this->assertCount(1, $results);
        $this->assertSame($affiliate->id, $results->first()->user_id);
    }

    /** @test */
    public function uses_rolling_window(): void
    {
        $this->createCompanyWithPlan();

        // Default rollingDays = 30. Transactions older than 30 days must NOT count.
        $affiliate = $this->createAffiliate('WindowTest');

        // Transaction 31 days ago — outside the 30-day window
        $this->createTransaction($affiliate, 95, null, 31, 'purchase', 'confirmed', true);

        // A small transaction in window so affiliate has some activity but ratio is low
        $customer = $this->createCustomer('Cust5', $this->nodeFor($affiliate));
        $this->createTransaction($affiliate, 5, null, 0, 'purchase', 'confirmed', true);
        $this->createTransaction($customer, 50, $affiliate, 0, 'purchase', 'confirmed', true);
        // In-window personal = 5, referred = 50, ratio = 5/55 ≈ 0.09 → NOT flagged

        $results = $this->detector->scan($this->company->id, $this->today);

        $this->assertCount(0, $results,
            'Old transaction outside rolling window must not push ratio above threshold');
    }
}
