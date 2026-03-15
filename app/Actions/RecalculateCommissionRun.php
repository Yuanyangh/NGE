<?php

namespace App\Actions;

use App\Models\CommissionRun;
use App\Models\Company;
use App\Services\Commission\CommissionRunOrchestrator;
use Carbon\Carbon;

class RecalculateCommissionRun
{
    public function __construct(
        private readonly CommissionRunOrchestrator $orchestrator,
    ) {}

    /**
     * Replay a historical commission run. The orchestrator's idempotency
     * logic deletes the old run and recreates it from scratch.
     */
    public function execute(Company $company, CommissionRun $run): CommissionRun
    {
        $runDate = Carbon::parse($run->run_date);

        return $this->orchestrator->run($company, $runDate);
    }
}
