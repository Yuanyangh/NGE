<?php

namespace Tests\Feature\Reporting;

use App\Services\Reporting\BreakageAnalysisService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Commission\CommissionTestCase;

class BreakageAnalysisServiceTest extends CommissionTestCase
{
    private BreakageAnalysisService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BreakageAnalysisService();
    }

    /** @test */
    public function calculates_wasted_volume(): void
    {
        $this->createCompanyWithPlan();
        $start = now()->subDays(30);
        $end = now();

        $a1 = $this->createAffiliate('Alice');

        // Below threshold (20 XP) — wasted
        $this->createTransaction($a1, 10, null, 10);
        $this->createTransaction($a1, 15, null, 5);

        // Above threshold — qualifying
        $this->createTransaction($a1, 30, null, 3);

        $result = $this->service->analyze($this->company->id, $start, $end);

        $this->assertSame(0, bccomp('25.00', $result->wastedVolumeXp, 2));
        $this->assertSame(2, $result->wastedTransactionCount);
    }

    /** @test */
    public function calculates_qualifying_volume(): void
    {
        $this->createCompanyWithPlan();
        $start = now()->subDays(30);
        $end = now();

        $a1 = $this->createAffiliate('Alice');

        $this->createTransaction($a1, 30, null, 10);
        $this->createTransaction($a1, 50, null, 5);
        $this->createTransaction($a1, 10, null, 3); // wasted

        $result = $this->service->analyze($this->company->id, $start, $end);

        $this->assertSame(0, bccomp('80.00', $result->qualifyingVolumeXp, 2));
    }

    /** @test */
    public function wasted_percentage_correct(): void
    {
        $this->createCompanyWithPlan();
        $start = now()->subDays(30);
        $end = now();

        $a1 = $this->createAffiliate('Alice');

        // 30 XP wasted (3 x 10, below threshold of 20)
        $this->createTransaction($a1, 10, null, 10);
        $this->createTransaction($a1, 10, null, 8);
        $this->createTransaction($a1, 10, null, 6);

        // 70 XP qualifying
        $this->createTransaction($a1, 35, null, 4);
        $this->createTransaction($a1, 35, null, 2);

        $result = $this->service->analyze($this->company->id, $start, $end);

        // 30 / (30+70) * 100 = 30%
        $this->assertSame(0, bccomp('30.00', $result->wastedPercentage, 2));
    }

    /** @test */
    public function reads_xp_threshold_from_plan(): void
    {
        $this->createCompanyWithPlan();
        $start = now()->subDays(30);
        $end = now();

        $result = $this->service->analyze($this->company->id, $start, $end);

        // SoComm plan config has active_customer_min_order_xp = 20
        $this->assertSame(20, $result->xpThreshold);
    }

    /** @test */
    public function counts_clawbacks(): void
    {
        $this->createCompanyWithPlan();
        $start = now()->subDays(30);
        $end = now();

        $a1 = $this->createAffiliate('Alice');

        $walletId = DB::table('wallet_accounts')
            ->where('user_id', $a1->id)
            ->value('id');

        if (!$walletId) {
            $walletId = DB::table('wallet_accounts')->insertGetId([
                'company_id' => $this->company->id,
                'user_id' => $a1->id,
                'currency' => 'USD',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('wallet_movements')->insert([
            'company_id' => $this->company->id,
            'wallet_account_id' => $walletId,
            'type' => 'clawback',
            'amount' => '-25.0000',
            'status' => 'approved',
            'effective_at' => now()->subDays(10)->toDateTimeString(),
            'created_at' => now()->subDays(10)->toDateTimeString(),
        ]);
        DB::table('wallet_movements')->insert([
            'company_id' => $this->company->id,
            'wallet_account_id' => $walletId,
            'type' => 'clawback',
            'amount' => '-15.0000',
            'status' => 'approved',
            'effective_at' => now()->subDays(5)->toDateTimeString(),
            'created_at' => now()->subDays(5)->toDateTimeString(),
        ]);

        $result = $this->service->analyze($this->company->id, $start, $end);

        $this->assertSame(0, bccomp('40.00', $result->clawbackTotal, 2));
        $this->assertSame(2, $result->clawbackCount);
    }

    /** @test */
    public function handles_empty_period(): void
    {
        $this->createCompanyWithPlan();
        $start = now()->subDays(30);
        $end = now();

        $result = $this->service->analyze($this->company->id, $start, $end);

        $this->assertSame(0, bccomp('0.00', $result->wastedVolumeXp, 2));
        $this->assertSame(0, bccomp('0.00', $result->qualifyingVolumeXp, 2));
        $this->assertSame(0, bccomp('0.00', $result->wastedPercentage, 2));
        $this->assertSame(0, $result->wastedTransactionCount);
        $this->assertSame(0, bccomp('0.00', $result->breakageRate, 2));
        $this->assertSame(0, $result->clawbackCount);
    }
}
