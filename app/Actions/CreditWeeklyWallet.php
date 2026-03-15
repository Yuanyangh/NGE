<?php

namespace App\Actions;

use App\Models\Company;
use App\Services\Commission\WalletCreditService;
use Carbon\Carbon;

class CreditWeeklyWallet
{
    public function __construct(
        private readonly WalletCreditService $walletCreditService,
    ) {}

    /**
     * @return array List of wallet movements created
     */
    public function execute(Company $company, ?Carbon $date = null): array
    {
        $date ??= Carbon::today();

        return $this->walletCreditService->creditFromCommissions($company, $date);
    }
}
