<?php

namespace App\Console\Commands;

use App\Actions\RecalculateCommissionRun;
use App\Models\CommissionRun;
use App\Models\Company;
use Illuminate\Console\Command;

class RecalculateCommissionCommand extends Command
{
    protected $signature = 'commissions:recalculate
        {company : Company slug}
        {run_id : ID of the commission run to recalculate}';

    protected $description = 'Replay a historical commission run from scratch';

    public function handle(RecalculateCommissionRun $action): int
    {
        $company = Company::where('slug', $this->argument('company'))->first();

        if (! $company) {
            $this->error("Company '{$this->argument('company')}' not found.");
            return self::FAILURE;
        }

        $run = CommissionRun::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->find($this->argument('run_id'));

        if (! $run) {
            $this->error("Commission run #{$this->argument('run_id')} not found for {$company->name}.");
            return self::FAILURE;
        }

        $this->info("Recalculating run #{$run->id} ({$run->run_date->toDateString()}) for {$company->name}...");

        try {
            $newRun = $action->execute($company, $run);

            $this->info('Recalculation completed.');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Status', $newRun->status],
                    ['Run Date', $newRun->run_date->toDateString()],
                    ['Affiliate Commissions', '$' . $newRun->total_affiliate_commission],
                    ['Viral Commissions', '$' . $newRun->total_viral_commission],
                    ['Company Volume (30d)', '$' . $newRun->total_company_volume],
                    ['Viral Cap Triggered', $newRun->viral_cap_triggered ? 'Yes' : 'No'],
                    ['Ledger Entries', $newRun->ledgerEntries()->count()],
                ]
            );

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Recalculation failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
