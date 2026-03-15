<?php

namespace Tests\Feature\Commission;

use App\Models\CommissionLedgerEntry;
use App\Models\CommissionRun;
use App\Models\Transaction;
use App\Services\Commission\CapEnforcer;
use Carbon\Carbon;

class CapEnforcerTest extends CommissionTestCase
{
    private CapEnforcer $enforcer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enforcer = new CapEnforcer();
        $this->createCompanyWithPlan();
    }

    /** @test — Section 8 Test 7: Viral Cap Enforcement */
    public function viral_cap_enforcement(): void
    {
        // Seed rolling 30-day company volume: 100,000 XP
        $buyer = $this->createCustomer('Buyer');
        for ($i = 1; $i <= 20; $i++) {
            Transaction::factory()->create([
                'company_id' => $this->company->id,
                'user_id' => $buyer->id,
                'xp' => 5000, // 20 * 5000 = 100,000
                'amount' => 5000,
                'status' => 'confirmed',
                'qualifies_for_commission' => true,
                'transaction_date' => $this->today->copy()->subDays($i),
            ]);
        }

        // Seed rolling viral commissions already paid: 14,750
        // Cap = 15% of 100,000 = 15,000. Total with today = 14,750 + 500 = 15,250
        // viralPct = 15,250/100,000 = 0.1525, overage = 0.0025
        // multiplier = 0.9975, adjusted = 500 * 0.9975 = 498.75
        $run = CommissionRun::factory()->create([
            'company_id' => $this->company->id,
            'compensation_plan_id' => $this->plan->id,
            'run_date' => $this->today->copy()->subDay(),
            'status' => 'completed',
        ]);

        $affiliate = $this->createAffiliate('Test Aff');
        CommissionLedgerEntry::factory()->create([
            'company_id' => $this->company->id,
            'commission_run_id' => $run->id,
            'user_id' => $affiliate->id,
            'type' => 'viral_commission',
            'amount' => 14750,
            'created_at' => $this->today->copy()->subDay(),
        ]);

        // Today's proposed viral payouts: $500 total across 2 affiliates
        $commissionResults = [
            [
                'user_id' => 100,
                'affiliate_commission' => '0',
                'viral_commission' => '300',
            ],
            [
                'user_id' => 101,
                'affiliate_commission' => '0',
                'viral_commission' => '200',
            ],
        ];

        $result = $this->enforcer->enforce(
            $commissionResults,
            $this->company->id,
            $this->today,
            $this->config,
        );

        $this->assertTrue($result['viral_cap_triggered']);

        // Total viral after reduction: $500 * 0.9975 = $498.75
        $totalViral = '0';
        foreach ($result['adjusted_results'] as $r) {
            $totalViral = bcadd($totalViral, $r['viral_commission'], 4);
        }

        $this->assertCommissionEquals('498.7500', $totalViral);
    }

    /** @test — No cap when under threshold */
    public function no_cap_when_under_threshold(): void
    {
        $commissionResults = [
            [
                'user_id' => 100,
                'affiliate_commission' => '10',
                'viral_commission' => '5',
            ],
        ];

        $result = $this->enforcer->enforce(
            $commissionResults,
            $this->company->id,
            $this->today,
            $this->config,
        );

        $this->assertFalse($result['viral_cap_triggered']);
        $this->assertFalse($result['global_cap_triggered']);

        // Amounts unchanged
        $this->assertCommissionEquals('10', $result['adjusted_results'][0]['affiliate_commission']);
        $this->assertCommissionEquals('5', $result['adjusted_results'][0]['viral_commission']);
    }
}
