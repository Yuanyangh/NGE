<?php

namespace Tests\Feature\Compliance;

use App\Events\TransactionRefunded;
use App\Listeners\ProcessRefundClawback;
use App\Models\Transaction;
use App\Models\WalletMovement;
use App\Scopes\CompanyScope;
use App\Services\Commission\ClawbackService;
use Illuminate\Support\Facades\Event;
use Tests\Feature\Commission\CommissionTestCase;

class TransactionRefundEventTest extends CommissionTestCase
{
    /** @test */
    public function create_refund_dispatches_event(): void
    {
        $this->createCompanyWithPlan();

        Event::fake([TransactionRefunded::class]);

        $affiliate = $this->createAffiliate('EventAffiliate');
        $customer  = $this->createCustomer('EventCustomer', $this->nodeFor($affiliate));

        // Create an original purchase transaction
        $original = $this->createTransaction($customer, 50, $affiliate, 5, 'purchase', 'confirmed', true);

        // Call the model's createRefund factory method.
        // Amount must not exceed the original (which has amount=50 from createTransaction).
        Transaction::createRefund($original, '50', '50');

        // Assert the event was dispatched exactly once
        Event::assertDispatched(TransactionRefunded::class, 1);

        // Assert the dispatched event carries a refund transaction (type='refund')
        Event::assertDispatched(TransactionRefunded::class, function (TransactionRefunded $event) {
            return $event->transaction->type === 'refund';
        });
    }

    /** @test */
    public function listener_calls_clawback_service(): void
    {
        $this->createCompanyWithPlan();

        $affiliate = $this->createAffiliate('ListenerAffiliate');
        $customer  = $this->createCustomer('ListenerCustomer', $this->nodeFor($affiliate));

        // Create original purchase
        $original = $this->createTransaction($customer, 50, $affiliate, 5, 'purchase', 'confirmed', true);

        // Set up a commission run and ledger entry so the service has something to claw back
        $runId = \Illuminate\Support\Facades\DB::table('commission_runs')->insertGetId([
            'company_id'                 => $this->company->id,
            'compensation_plan_id'       => $this->plan->id,
            'run_date'                   => $this->today->copy()->subDays(5)->toDateString(),
            'status'                     => 'completed',
            'total_affiliate_commission' => '0',
            'total_viral_commission'     => '0',
            'total_bonus_amount'         => '0',
            'total_company_volume'       => '0',
            'viral_cap_triggered'        => false,
            'started_at'                 => now()->toDateTimeString(),
            'completed_at'               => now()->toDateTimeString(),
            'created_at'                 => now()->toDateTimeString(),
            'updated_at'                 => now()->toDateTimeString(),
        ]);

        $entryId = \Illuminate\Support\Facades\DB::table('commission_ledger_entries')->insertGetId([
            'company_id'        => $this->company->id,
            'commission_run_id' => $runId,
            'user_id'           => $affiliate->id,
            'type'              => 'affiliate_commission',
            'amount'            => '50.0000',
            'tier_achieved'     => 1,
            'created_at'        => now()->toDateTimeString(),
        ]);

        // Use a mock to verify ClawbackService::processRefund is invoked by the listener
        $mockService = $this->createMock(ClawbackService::class);
        $mockService->expects($this->once())
            ->method('processRefund')
            ->willReturn(new \App\DTOs\ClawbackResult(
                user_id: $affiliate->id,
                original_transaction_id: $original->id,
                refund_transaction_id: 0,
                total_clawback_amount: '50.0000',
                clawback_details: [],
                is_partial: false,
            ));

        // Resolve listener manually with the mock service
        $listener = new ProcessRefundClawback($mockService);

        // Create a refund transaction directly (no event dispatch so we control the flow)
        $refund = Transaction::withoutGlobalScopes()->create([
            'company_id'             => $this->company->id,
            'user_id'                => $customer->id,
            'referred_by_user_id'    => $affiliate->id,
            'type'                   => 'refund',
            'amount'                 => '500.00',
            'xp'                     => '50',
            'currency'               => 'USD',
            'status'                 => 'confirmed',
            'qualifies_for_commission' => false,
            'transaction_date'       => now()->toDateString(),
            'reference'              => (string) $original->id,
        ]);

        $event = new TransactionRefunded($refund);
        $listener->handle($event);

        // The mock assertion (expects()->once()) verifies the service was called
    }
}
