<?php

namespace App\Listeners;

use App\Events\TransactionRefunded;
use App\Exceptions\Commission\ClawbackException;
use App\Services\Commission\ClawbackService;
use Illuminate\Support\Facades\Log;

class ProcessRefundClawback
{
    public function __construct(
        private readonly ClawbackService $clawbackService,
    ) {}

    public function handle(TransactionRefunded $event): void
    {
        try {
            $result = $this->clawbackService->processRefund($event->transaction);

            Log::info(sprintf(
                'Clawback processed for refund #%d: total=%s, entries=%d',
                $event->transaction->id,
                $result->total_clawback_amount,
                count($result->clawback_details),
            ));
        } catch (ClawbackException $e) {
            Log::error("Clawback failed for transaction #{$event->transaction->id}: {$e->getMessage()}");
        }
    }
}
