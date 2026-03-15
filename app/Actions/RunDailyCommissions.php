<?php

namespace App\Actions;

use App\Models\CommissionRun;
use App\Models\Company;
use App\Services\Commission\CommissionRunOrchestrator;
use Carbon\Carbon;

class RunDailyCommissions
{
    public function __construct(
        private readonly CommissionRunOrchestrator $orchestrator,
    ) {}

    public function execute(Company $company, ?Carbon $date = null): CommissionRun
    {
        $date ??= Carbon::today();

        return $this->orchestrator->run($company, $date);
    }
}
