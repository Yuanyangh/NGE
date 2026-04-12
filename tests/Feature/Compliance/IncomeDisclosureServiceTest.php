<?php

namespace Tests\Feature\Compliance;

use App\Models\BonusLedgerEntry;
use App\Models\CommissionLedgerEntry;
use App\Models\CommissionRun;
use App\Scopes\CompanyScope;
use App\Services\Reporting\IncomeDisclosureService;
use Carbon\Carbon;
use Tests\Feature\Commission\CommissionTestCase;

class IncomeDisclosureServiceTest extends CommissionTestCase
{
    private IncomeDisclosureService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new IncomeDisclosureService();
    }

    /** @test */
    public function generates_correct_brackets_for_mixed_earnings(): void
    {
        $this->createCompanyWithPlan();

        // Create 4 affiliates with varying earnings
        $a1 = $this->createAffiliate('Alice');   // will earn $50 (bracket: $1–$100)
        $a2 = $this->createAffiliate('Bob');     // will earn $200 (bracket: $101–$500)
        $a3 = $this->createAffiliate('Carol');   // will earn $0 (bracket: $0)
        $a4 = $this->createAffiliate('Dave');    // will earn $1500 (bracket: $1001–$5000)

        $start = Carbon::create(2026, 3, 1);
        $end   = Carbon::create(2026, 3, 31);

        // Seed commission_ledger_entries directly
        $this->seedCommissionEntry($a1->id, '50.00', $start->copy()->addDays(5));
        $this->seedCommissionEntry($a2->id, '200.00', $start->copy()->addDays(10));
        $this->seedCommissionEntry($a4->id, '1500.00', $start->copy()->addDays(15));
        // a3 gets nothing

        $result = $this->service->generate($this->company->id, $start, $end);

        $this->assertSame(4, $result->totalAffiliates);
        $this->assertSame(3, $result->activeAffiliates);
        $this->assertSame(1, $result->inactiveAffiliates);

        $brackets = collect($result->brackets)->keyBy('label');

        // $0 bracket: Carol only
        $this->assertSame(1, $brackets['$0']['count']);
        $this->assertEquals(25.0, $brackets['$0']['percentage']);

        // $1–$100 bracket: Alice
        $this->assertSame(1, $brackets['$1 – $100']['count']);
        $this->assertEquals(25.0, $brackets['$1 – $100']['percentage']);

        // $101–$500 bracket: Bob
        $this->assertSame(1, $brackets['$101 – $500']['count']);
        $this->assertEquals(25.0, $brackets['$101 – $500']['percentage']);

        // $1001–$5000 bracket: Dave
        $this->assertSame(1, $brackets['$1,001 – $5,000']['count']);
        $this->assertEquals(25.0, $brackets['$1,001 – $5,000']['percentage']);
    }

    /** @test */
    public function handles_all_zero_earners(): void
    {
        $this->createCompanyWithPlan();

        // 3 affiliates, none earn anything
        $this->createAffiliate('Zero1');
        $this->createAffiliate('Zero2');
        $this->createAffiliate('Zero3');

        $start = Carbon::create(2026, 3, 1);
        $end   = Carbon::create(2026, 3, 31);

        $result = $this->service->generate($this->company->id, $start, $end);

        $this->assertSame(3, $result->totalAffiliates);
        $this->assertSame(0, $result->activeAffiliates);
        $this->assertSame(3, $result->inactiveAffiliates);
        $this->assertSame(0, bccomp('0.00', $result->totalPaidOut, 2));
        $this->assertSame(0, bccomp('0.00', $result->medianEarnings, 2));

        $zeroBracket = collect($result->brackets)->firstWhere('label', '$0');
        $this->assertSame(3, $zeroBracket['count']);
        $this->assertEquals(100.0, $zeroBracket['percentage']);
    }

    /** @test */
    public function handles_empty_company(): void
    {
        $this->createCompanyWithPlan();
        // No affiliates created at all

        $start = Carbon::create(2026, 3, 1);
        $end   = Carbon::create(2026, 3, 31);

        $result = $this->service->generate($this->company->id, $start, $end);

        $this->assertSame(0, $result->totalAffiliates);
        $this->assertSame(0, $result->activeAffiliates);
        $this->assertSame(0, $result->inactiveAffiliates);
        $this->assertSame(0, bccomp('0.00', $result->totalPaidOut, 2));
        $this->assertSame('2026-03-01', $result->periodStart);
        $this->assertSame('2026-03-31', $result->periodEnd);

        // All brackets should be zero
        foreach ($result->brackets as $bracket) {
            $this->assertSame(0, $bracket['count']);
            $this->assertEquals(0.0, $bracket['percentage']);
        }
    }

    /** @test */
    public function calculates_median_and_percentiles(): void
    {
        $this->createCompanyWithPlan();

        // 10 affiliates with known earnings (sorted descending: 1000, 900, 800, 700, 600, 500, 400, 300, 200, 100)
        $amounts = [100, 200, 300, 400, 500, 600, 700, 800, 900, 1000];
        $start = Carbon::create(2026, 3, 1);
        $end   = Carbon::create(2026, 3, 31);

        foreach ($amounts as $amount) {
            $a = $this->createAffiliate("A{$amount}");
            $this->seedCommissionEntry($a->id, (string) $amount, $start->copy()->addDays(5));
        }

        $result = $this->service->generate($this->company->id, $start, $end);

        $this->assertSame(10, $result->totalAffiliates);

        // Median of [1000,900,800,700,600,500,400,300,200,100] (10 elements, even count)
        // Middle two are 600 and 500 → median = (600+500)/2 = 550
        $this->assertSame(0, bccomp('550.00', $result->medianEarnings, 2),
            "Expected median 550.00, got {$result->medianEarnings}");

        // Mean = (1000+900+800+700+600+500+400+300+200+100)/10 = 550
        $this->assertSame(0, bccomp('550.00', $result->meanEarnings, 2),
            "Expected mean 550.00, got {$result->meanEarnings}");

        // Top 1%: ceil(10 * 1/100) = 1 → top earner is 1000
        $this->assertSame(0, bccomp('1000.00', $result->top1PercentThreshold, 2),
            "Expected top 1% threshold 1000.00, got {$result->top1PercentThreshold}");

        // Top 10%: ceil(10 * 10/100) = 1 → also 1000
        $this->assertSame(0, bccomp('1000.00', $result->top10PercentThreshold, 2),
            "Expected top 10% threshold 1000.00, got {$result->top10PercentThreshold}");
    }

    /** @test */
    public function respects_date_range_filter(): void
    {
        $this->createCompanyWithPlan();

        $a1 = $this->createAffiliate('InRange');
        $a2 = $this->createAffiliate('OutOfRange');

        $start = Carbon::create(2026, 3, 1);
        $end   = Carbon::create(2026, 3, 31);

        // In-range: within March
        $this->seedCommissionEntry($a1->id, '300.00', Carbon::create(2026, 3, 15));

        // Out-of-range: in February (before start) and April (after end)
        $this->seedCommissionEntry($a2->id, '500.00', Carbon::create(2026, 2, 28));
        $this->seedCommissionEntry($a2->id, '500.00', Carbon::create(2026, 4, 1));

        $result = $this->service->generate($this->company->id, $start, $end);

        $this->assertSame(2, $result->totalAffiliates);
        // Only a1 should have earnings counted; a2 appears as zero-earner for this period
        $this->assertSame(1, $result->activeAffiliates);
        $this->assertSame(1, $result->inactiveAffiliates);

        $this->assertSame(0, bccomp('300.00', $result->totalPaidOut, 2),
            "Expected totalPaidOut 300.00, got {$result->totalPaidOut}");
    }

    /** @test */
    public function includes_bonus_earnings(): void
    {
        $this->createCompanyWithPlan();

        $a1 = $this->createAffiliate('BonusEarner');

        $start = Carbon::create(2026, 3, 1);
        $end   = Carbon::create(2026, 3, 31);

        // $100 commission + $50 bonus = $150 total
        $this->seedCommissionEntry($a1->id, '100.00', Carbon::create(2026, 3, 10));
        $this->seedBonusEntry($a1->id, '50.00', Carbon::create(2026, 3, 10));

        $result = $this->service->generate($this->company->id, $start, $end);

        $this->assertSame(1, $result->totalAffiliates);
        $this->assertSame(1, $result->activeAffiliates);

        $this->assertSame(0, bccomp('150.00', $result->totalPaidOut, 2),
            "Expected totalPaidOut 150.00 (commission+bonus), got {$result->totalPaidOut}");

        // Should land in $101–$500 bracket
        $bracket = collect($result->brackets)->firstWhere('label', '$101 – $500');
        $this->assertSame(1, $bracket['count']);
    }

    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    /**
     * Insert a commission_ledger_entry row directly for testing income disclosure.
     * Bypasses CommissionRun to keep these tests focused.
     */
    private function seedCommissionEntry(int $userId, string $amount, Carbon $createdAt): void
    {
        // We need a minimal CommissionRun row to satisfy FK (if any)
        // Commission ledger entries need a commission_run_id
        $runId = $this->getOrCreateRunId($createdAt);

        \Illuminate\Support\Facades\DB::table('commission_ledger_entries')->insert([
            'company_id'        => $this->company->id,
            'commission_run_id' => $runId,
            'user_id'           => $userId,
            'type'              => 'affiliate_commission',
            'amount'            => $amount,
            'tier_achieved'     => 1,
            'created_at'        => $createdAt->toDateTimeString(),
        ]);
    }

    /**
     * Insert a bonus_ledger_entry row directly for testing income disclosure.
     */
    private function seedBonusEntry(int $userId, string $amount, Carbon $createdAt): void
    {
        $runId = $this->getOrCreateRunId($createdAt);

        \Illuminate\Support\Facades\DB::table('bonus_ledger_entries')->insert([
            'company_id'        => $this->company->id,
            'commission_run_id' => $runId,
            'user_id'           => $userId,
            'bonus_type_id'     => $this->getOrCreateBonusTypeId(),
            'amount'            => $amount,
            'description'       => 'test bonus',
            'created_at'        => $createdAt->toDateTimeString(),
        ]);
    }

    private array $runIds = [];

    private function getOrCreateRunId(Carbon $date): int
    {
        $key = $date->toDateString();
        if (!isset($this->runIds[$key])) {
            $this->runIds[$key] = \Illuminate\Support\Facades\DB::table('commission_runs')->insertGetId([
                'company_id'                  => $this->company->id,
                'compensation_plan_id'        => $this->plan->id,
                'run_date'                    => $date->toDateString(),
                'status'                      => 'completed',
                'total_affiliate_commission'  => '0',
                'total_viral_commission'      => '0',
                'total_bonus_amount'          => '0',
                'total_company_volume'        => '0',
                'viral_cap_triggered'         => false,
                'started_at'                  => $date->toDateTimeString(),
                'completed_at'                => $date->toDateTimeString(),
                'created_at'                  => $date->toDateTimeString(),
                'updated_at'                  => $date->toDateTimeString(),
            ]);
        }
        return $this->runIds[$key];
    }

    private ?int $bonusTypeId = null;

    private function getOrCreateBonusTypeId(): int
    {
        if ($this->bonusTypeId === null) {
            $this->bonusTypeId = \Illuminate\Support\Facades\DB::table('bonus_types')->insertGetId([
                'company_id'           => $this->company->id,
                'compensation_plan_id' => $this->plan->id,
                'type'                 => 'matching',
                'name'                 => 'Test Bonus',
                'is_active'            => true,
                'priority'             => 0,
                'created_at'           => now()->toDateTimeString(),
                'updated_at'           => now()->toDateTimeString(),
            ]);
        }
        return $this->bonusTypeId;
    }
}
