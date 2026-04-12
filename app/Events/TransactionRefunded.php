<?php

namespace App\Events;

use App\Models\Transaction;
use Illuminate\Foundation\Events\Dispatchable;

class TransactionRefunded
{
    use Dispatchable;

    public function __construct(
        public readonly Transaction $transaction,
    ) {}
}
