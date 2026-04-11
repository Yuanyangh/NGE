<?php

namespace App\Services\Commission\Bonus;

use App\DTOs\BonusOrchestratorResult;
use App\DTOs\PlanConfig;
use App\Models\BonusLedgerEntry;
use App\Models\BonusType;
use App\Models\CommissionRun;
use App\Scopes\CompanyScope;
use App\Services\Commission\CapEnforcer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BonusOrchestrator
{
    public function __construct(
        private readonly BonusDispatcher $dispatcher,
        private readonly CapEnforcer $capEnforcer,
    ) {}

    /**
     * Run all active bonus types for a commission run.
     *
     * 1. Load bonus_types ordered by priority ASC for the company + plan.
     * 2. Skip inactive types.
     * 3. Dispatch each active type to its calculator.
     * 4. Write BonusLedgerEntry rows for every non-zero result.
     * 5. Sum all bonus amounts and store total_bonus_amount on CommissionRun.
     * 6. Apply the global 35% cap to (commission + bonuses) combined.
     */
    public function run(
        CommissionRun $run,
        Collection $affiliates,
        Collection $commissionResults,
        Carbon $date,
    ): BonusOrchestratorResult {
        $companyId = $run->company_id;
        $planId = $run->compensation_plan_id;

        // 1. Load active bonus types ordered by priority
        $activeBonusTypes = BonusType::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $companyId)
            ->where('compensation_plan_id', $planId)
            ->where('is_active', true)
            ->orderBy('priority')
            ->get();

        // No active bonus types: return zero result
        if ($activeBonusTypes->isEmpty()) {
            return new BonusOrchestratorResult(
                total_bonus_amount: '0',
                entries: collect(),
                cap_triggered: false,
            );
        }

        // 2-3. Dispatch each active type to its calculator
        $allBonusResults = collect();

        foreach ($activeBonusTypes as $bonusType) {
            $calculator = $this->dispatcher->getCalculator($bonusType->type);
            $bonusResults = $calculator->calculate(
                $bonusType,
                $affiliates,
                $commissionResults,
                $date,
            );
            $allBonusResults = $allBonusResults->merge($bonusResults);
        }

        // 6. Check global cap including bonus amounts
        $capTriggered = false;
        if ($allBonusResults->isNotEmpty()) {
            $plan = $run->compensationPlan;
            $config = PlanConfig::fromArray($plan->config);

            $commResultsArray = $commissionResults->toArray();
            $globalCapResult = $this->capEnforcer->enforceGlobalCapWithBonuses(
                $commResultsArray,
                $allBonusResults,
                $companyId,
                $date,
                $config,
            );

            if ($globalCapResult['global_cap_with_bonus_triggered']) {
                $capTriggered = true;
                $allBonusResults = $globalCapResult['adjusted_bonuses'];
            }
        }

        // 4. Write BonusLedgerEntry rows and sum totals
        $totalBonusAmount = '0';
        $entries = collect();

        DB::transaction(function () use ($allBonusResults, $run, $companyId, &$totalBonusAmount, &$entries) {
            foreach ($allBonusResults as $bonus) {
                if (bccomp($bonus->amount, '0', 4) > 0) {
                    $entry = BonusLedgerEntry::create([
                        'company_id' => $companyId,
                        'commission_run_id' => $run->id,
                        'user_id' => $bonus->user_id,
                        'bonus_type_id' => $bonus->bonus_type_id,
                        'amount' => $bonus->amount,
                        'tier_achieved' => $bonus->tier_achieved,
                        'qualification_snapshot' => $bonus->qualification_snapshot,
                        'description' => $bonus->description,
                        'created_at' => now(),
                    ]);

                    $entries->push($entry);
                    $totalBonusAmount = bcadd($totalBonusAmount, $bonus->amount, 4);
                }
            }
        });

        // 5. Update CommissionRun.total_bonus_amount
        $run->update([
            'total_bonus_amount' => $totalBonusAmount,
        ]);

        return new BonusOrchestratorResult(
            total_bonus_amount: $totalBonusAmount,
            entries: $entries,
            cap_triggered: $capTriggered,
        );
    }
}
