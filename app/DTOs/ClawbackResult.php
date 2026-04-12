<?php

namespace App\DTOs;

readonly class ClawbackResult
{
    /**
     * @param int $user_id The affiliate whose commissions were clawed back
     * @param int $original_transaction_id The original transaction that was refunded
     * @param int $refund_transaction_id The refund transaction that triggered the clawback
     * @param string $total_clawback_amount Total clawback amount (bcmath string, always positive)
     * @param array $clawback_details Individual clawback entries with ledger/movement references
     * @param bool $is_partial Whether this was a partial refund
     */
    public function __construct(
        public int $user_id,
        public int $original_transaction_id,
        public int $refund_transaction_id,
        public string $total_clawback_amount,
        public array $clawback_details,
        public bool $is_partial,
    ) {}
}
