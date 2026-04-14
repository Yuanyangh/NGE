<?php

namespace Tests\Feature\Reporting;

use App\Services\Reporting\KpiDashboardService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Commission\CommissionTestCase;

class KpiDashboardServiceTest extends CommissionTestCase
{
    private KpiDashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new KpiDashboardService();
    }

    /** @test */
    public function generates_correct_volume_totals(): void
    {
        $this->createCompanyWithPlan();
        $start = now()->subDays(30);
        $end = now();

        // 2 runs in range
        $this->seedRun(now()->subDays(10), '500.00', '50.00', '20.00', '10.00');
        $this->seedRun(now()->subDays(5), '300.00', '30.00', '10.00', '5.00');
        // 1 run outside range
        $this->seedRun(now()->addDays(5), '9999.00', '999.00', '999.00', '999.00');

        $result = $this->service->generate($this->company->id, $start, $end);

        $this->assertSame(0, bccomp('800.00', $result->totalVolume, 2));
        $this->assertSame(0, bccomp('110.00', $result->totalCommissions, 2));
        $this->assertSame(0, bccomp('15.00', $result->totalBonuses, 2));
    }

    /** @test */
    public function calculates_payout_ratio(): void
    {
        $this->createCompanyWithPlan();
        $start = now()->subDays(30);
        $end = now();

        $this->seedRun(now()->subDays(5), '1000.00', '80.00', '20.00', '50.00');

        $result = $this->service->generate($this->company->id, $start, $end);

        // (80+20+50) / 1000 * 100 = 15%
        $this->assertSame(0, bccomp('15.00', $result->payoutRatio, 2));
    }

    /** @test */
    public function counts_active_affiliates(): void
    {
        $this->createCompanyWithPlan();
        $start = now()->subDays(30);
        $end = now();

        $a1 = $this->createAffiliate('Earner1');
        $a2 = $this->createAffiliate('Earner2');
        $a3 = $this->createAffiliate('NonEarner');

        $runId = $this->seedRun(now()->subDays(5), '1000.00', '100.00', '50.00', '0.00');

        $this->seedLedgerEntry($runId, $a1->id, '50.00', now()->subDays(5));
        $this->seedLedgerEntry($runId, $a2->id, '50.00', now()->subDays(5));

        $result = $this->service->generate($this->company->id, $start, $end);

        $this->assertSame(2, $result->activeAffiliates);
        $this->assertSame(3, $result->totalAffiliates);
    }

    /** @test */
    public function counts_new_enrollments(): void
    {
        $this->createCompanyWithPlan();
        $start = now()->subDays(30);
        $end = now();

        $this->createAffiliate('InRange1');
        DB::table('users')->where('name', 'InRange1')->update(['enrolled_at' => now()->subDays(10)]);
        $this->createAffiliate('InRange2');
        DB::table('users')->where('name', 'InRange2')->update(['enrolled_at' => now()->subDays(5)]);
        $this->createAffiliate('OutOfRange');
        DB::table('users')->where('name', 'OutOfRange')->update(['enrolled_at' => now()->addDays(5)]);

        $result = $this->service->generate($this->company->id, $start, $end);

        $this->assertSame(2, $result->newEnrollments);
    }

    /** @test */
    public function top_earners_ordered_by_total(): void
    {
        $this->createCompanyWithPlan();
        $start = now()->subDays(30);
        $end = now();

        $alice = $this->createAffiliate('Alice');
        $bob = $this->createAffiliate('Bob');
        $carol = $this->createAffiliate('Carol');

        $runId = $this->seedRun(now()->subDays(5), '1000.00', '100.00', '50.00', '0.00');

        $this->seedLedgerEntry($runId, $alice->id, '70.00', now()->subDays(5));
        $this->seedLedgerEntry($runId, $bob->id, '30.00', now()->subDays(5));
        $this->seedLedgerEntry($runId, $carol->id, '10.00', now()->subDays(5));

        $result = $this->service->generate($this->company->id, $start, $end);

        $this->assertCount(3, $result->topEarners);
        $this->assertSame('Alice', $result->topEarners[0]['name']);
        $this->assertSame('Bob', $result->topEarners[1]['name']);
        $this->assertSame('Carol', $result->topEarners[2]['name']);
    }

    /** @test */
    public function handles_empty_period(): void
    {
        $this->createCompanyWithPlan();
        $start = now()->subDays(30);
        $end = now();

        $result = $this->service->generate($this->company->id, $start, $end);

        $this->assertSame(0, bccomp('0.00', $result->totalVolume, 2));
        $this->assertSame(0, bccomp('0.00', $result->totalCommissions, 2));
        $this->assertSame(0, bccomp('0.00', $result->payoutRatio, 2));
        $this->assertSame(0, $result->activeAffiliates);
        $this->assertSame(0, $result->commissionRunCount);
        $this->assertEmpty($result->topEarners);
        $this->assertEmpty($result->volumeTrend);
    }

    /** @test */
    public function viral_cap_triggered_count_correct(): void
    {
        $this->createCompanyWithPlan();
        $start = now()->subDays(30);
        $end = now();

        $this->seedRun(now()->subDays(10), '100.00', '10.00', '5.00', '0.00', true);
        $this->seedRun(now()->subDays(5), '100.00', '10.00', '5.00', '0.00', true);
        $this->seedRun(now()->subDays(3), '100.00', '10.00', '5.00', '0.00', false);

        $result = $this->service->generate($this->company->id, $start, $end);

        $this->assertSame(2, $result->viralCapTriggeredCount);
    }

    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    private function seedRun(
        Carbon $date,
        string $volume,
        string $affComm,
        string $viralComm,
        string $bonus,
        bool $viralCap = false,
    ): int {
        return DB::table('commission_runs')->insertGetId([
            'company_id' => $this->company->id,
            'compensation_plan_id' => $this->plan->id,
            'run_date' => $date->toDateString(),
            'status' => 'completed',
            'total_company_volume' => $volume,
            'total_affiliate_commission' => $affComm,
            'total_viral_commission' => $viralComm,
            'total_bonus_amount' => $bonus,
            'viral_cap_triggered' => $viralCap,
            'started_at' => $date->toDateTimeString(),
            'completed_at' => $date->toDateTimeString(),
            'created_at' => $date->toDateTimeString(),
            'updated_at' => $date->toDateTimeString(),
        ]);
    }

    private function seedLedgerEntry(int $runId, int $userId, string $amount, Carbon $date): void
    {
        DB::table('commission_ledger_entries')->insert([
            'company_id' => $this->company->id,
            'commission_run_id' => $runId,
            'user_id' => $userId,
            'type' => 'affiliate_commission',
            'amount' => $amount,
            'tier_achieved' => 1,
            'created_at' => $date->toDateTimeString(),
        ]);
    }
}
