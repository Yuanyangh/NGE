<?php

namespace App\Console\Commands;

use App\Actions\CreditWeeklyWallet;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CreditWalletCommand extends Command
{
    protected $signature = 'wallet:credit
        {company : Company slug}
        {--date= : Effective date (YYYY-MM-DD, defaults to today)}';

    protected $description = 'Credit wallet accounts from approved commissions';

    public function handle(CreditWeeklyWallet $action): int
    {
        $company = Company::where('slug', $this->argument('company'))->first();

        if (! $company) {
            $this->error("Company '{$this->argument('company')}' not found.");
            return self::FAILURE;
        }

        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::today();

        $this->info("Crediting wallets for {$company->name} on {$date->toDateString()}...");

        try {
            $credits = $action->execute($company, $date);

            $this->info(count($credits) . ' wallet movement(s) created.');

            if (count($credits) > 0) {
                $rows = array_map(fn ($c) => [
                    $c['user_id'],
                    '$' . $c['amount'],
                    $c['type'],
                ], $credits);

                $this->table(['User ID', 'Amount', 'Type'], $rows);
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Wallet credit failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
