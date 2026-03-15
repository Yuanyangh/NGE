<?php

namespace App\Console\Commands;

use App\Actions\RunDailyCommissions;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RunCommissionsCommand extends Command
{
    protected $signature = 'commissions:run
        {company : Company slug}
        {--date= : Run date (YYYY-MM-DD, defaults to today)}';

    protected $description = 'Run daily commission calculations for a company';

    public function handle(RunDailyCommissions $action): int
    {
        $company = Company::where('slug', $this->argument('company'))->first();

        if (! $company) {
            $this->error("Company '{$this->argument('company')}' not found.");
            return self::FAILURE;
        }

        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::today();

        $this->info("Running commissions for {$company->name} on {$date->toDateString()}...");

        try {
            $run = $action->execute($company, $date);

            $this->info('Commission run completed.');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Status', $run->status],
                    ['Run Date', $run->run_date->toDateString()],
                    ['Affiliate Commissions', '$' . $run->total_affiliate_commission],
                    ['Viral Commissions', '$' . $run->total_viral_commission],
                    ['Company Volume (30d)', '$' . $run->total_company_volume],
                    ['Viral Cap Triggered', $run->viral_cap_triggered ? 'Yes' : 'No'],
                    ['Ledger Entries', $run->ledgerEntries()->count()],
                ]
            );

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Commission run failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
