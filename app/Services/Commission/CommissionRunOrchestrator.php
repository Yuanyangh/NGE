<?php

namespace App\Services\Commission;

use App\DTOs\CommissionResult;
use App\DTOs\PlanConfig;
use App\Models\CommissionLedgerEntry;
use App\Models\CommissionRun;
use App\Models\Company;
use App\Models\CompensationPlan;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WalletMovement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommissionRunOrchestrator
{
    public function __construct(
        private readonly QualificationEvaluator $qualificationEvaluator,
        private readonly DirectCommissionCalculator $directCalculator,
        private readonly LegAggregator $legAggregator,
        private readonly QvvCalculator $qvvCalculator,
        private readonly ViralCommissionCalculator $viralCalculator,
        private readonly CapEnforcer $capEnforcer,
    ) {}

    public function run(Company $company, Carbon $date): CommissionRun
    {
        Log::info("Starting commission run for {$company->slug} on {$date->toDateString()}");

        // 1. Get active plan for this company + date
        $plan = $this->getActivePlan($company, $date);
        $config = PlanConfig::fromArray($plan->config);

        // 2. Ensure idempotency — delete existing run for this company+date
        $this->deleteExistingRun($company, $date);

        // 3. Create the commission run record
        $run = CommissionRun::create([
            'company_id' => $company->id,
            'compensation_plan_id' => $plan->id,
            'run_date' => $date->toDateString(),
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            // 4. Get all affiliates for this company
            $affiliates = User::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('role', 'affiliate')
                ->where('status', 'active')
                ->get();

            // 5. Calculate commissions for each affiliate
            $rawResults = [];

            foreach ($affiliates as $affiliate) {
                $result = $this->calculateForAffiliate($affiliate, $date, $config);
                $rawResults[] = $result;
            }

            // 6. Apply caps
            $capResult = $this->capEnforcer->enforce($rawResults, $company->id, $date, $config);

            // 7. Get rolling 30-day company volume for the run record
            $windowStart = $date->copy()->subDays($config->rolling_days - 1);
            $totalCompanyVolume = Transaction::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('status', 'confirmed')
                ->where('qualifies_for_commission', true)
                ->whereBetween('transaction_date', [$windowStart->toDateString(), $date->toDateString()])
                ->sum('xp');

            // 8. Write ledger entries
            $totalAffiliate = '0';
            $totalViral = '0';

            DB::transaction(function () use ($capResult, $run, $company, &$totalAffiliate, &$totalViral) {
                foreach ($capResult['adjusted_results'] as $result) {
                    // Affiliate commission entry
                    if (bccomp($result['affiliate_commission'], '0', 4) > 0) {
                        CommissionLedgerEntry::create([
                            'company_id' => $company->id,
                            'commission_run_id' => $run->id,
                            'user_id' => $result['user_id'],
                            'type' => 'affiliate_commission',
                            'amount' => $result['affiliate_commission'],
                            'tier_achieved' => $result['affiliate_tier_index'] !== null
                                ? $result['affiliate_tier_index'] + 1
                                : null,
                            'qualification_snapshot' => $result['qualification_snapshot'],
                            'description' => sprintf(
                                'Affiliate commission: tier %s at %.0f%%',
                                $result['affiliate_tier_index'] !== null ? $result['affiliate_tier_index'] + 1 : 'N/A',
                                ($result['affiliate_tier_rate'] ?? 0) * 100
                            ),
                            'created_at' => now(),
                        ]);

                        $totalAffiliate = bcadd($totalAffiliate, $result['affiliate_commission'], 4);
                    }

                    // Viral commission entry
                    if (bccomp($result['viral_commission'], '0', 4) > 0) {
                        CommissionLedgerEntry::create([
                            'company_id' => $company->id,
                            'commission_run_id' => $run->id,
                            'user_id' => $result['user_id'],
                            'type' => 'viral_commission',
                            'amount' => $result['viral_commission'],
                            'tier_achieved' => $result['viral_tier'],
                            'qualification_snapshot' => $result['qualification_snapshot'],
                            'description' => sprintf(
                                'Viral commission: tier %s, daily reward',
                                $result['viral_tier'] ?? 'N/A'
                            ),
                            'created_at' => now(),
                        ]);

                        $totalViral = bcadd($totalViral, $result['viral_commission'], 4);
                    }
                }

                // Write cap adjustment entries if caps were triggered
                if ($capResult['viral_cap_triggered'] || $capResult['global_cap_triggered']) {
                    $this->writeCapAdjustmentEntries($capResult, $run, $company);
                }
            });

            // 9. Mark run as completed
            $run->update([
                'status' => 'completed',
                'total_affiliate_commission' => $totalAffiliate,
                'total_viral_commission' => $totalViral,
                'total_company_volume' => $totalCompanyVolume,
                'viral_cap_triggered' => $capResult['viral_cap_triggered'],
                'viral_cap_reduction_pct' => $capResult['viral_cap_triggered']
                    ? $capResult['viral_reduction_pct']
                    : null,
                'completed_at' => now(),
            ]);

            Log::info("Commission run completed for {$company->slug}: affiliate={$totalAffiliate}, viral={$totalViral}");

            return $run->fresh();
        } catch (\Throwable $e) {
            $run->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            Log::error("Commission run failed for {$company->slug}: {$e->getMessage()}");

            throw $e;
        }
    }

    private function calculateForAffiliate(User $affiliate, Carbon $date, PlanConfig $config): array
    {
        $qualification = $this->qualificationEvaluator->evaluate($affiliate, $date, $config);

        $affiliateCommission = '0';
        $affiliateTierIndex = null;
        $affiliateTierRate = null;
        $viralCommission = '0';
        $viralTier = null;
        $volumeSnapshot = null;

        // Affiliate commission
        if ($qualification->affiliate_tier_index !== null) {
            $affiliateTierIndex = $qualification->affiliate_tier_index;
            $affiliateTierRate = $qualification->affiliate_tier_rate;

            $affiliateCommission = $this->directCalculator->calculate(
                $affiliate,
                $date,
                $config,
                $affiliateTierIndex,
                $affiliateTierRate,
            );
        }

        // Viral commission
        $legVolumes = $this->legAggregator->getLegVolumes($affiliate, $date, $config);

        if (! empty($legVolumes)) {
            $volumeSnapshot = $this->qvvCalculator->calculate($legVolumes, $config);

            $viralResult = $this->viralCalculator->calculate(
                $qualification->active_customer_count,
                $qualification->referred_volume_30d,
                $volumeSnapshot,
                $config,
            );

            $viralCommission = $viralResult['amount'];
            $viralTier = $viralResult['tier'];
        }

        return [
            'user_id' => $affiliate->id,
            'affiliate_commission' => $affiliateCommission,
            'affiliate_tier_index' => $affiliateTierIndex,
            'affiliate_tier_rate' => $affiliateTierRate,
            'viral_commission' => $viralCommission,
            'viral_tier' => $viralTier,
            'volume_snapshot' => $volumeSnapshot,
            'qualification_snapshot' => [
                'active_customer_count' => $qualification->active_customer_count,
                'referred_volume_30d' => $qualification->referred_volume_30d,
                'affiliate_tier_index' => $qualification->affiliate_tier_index,
                'affiliate_tier_rate' => $qualification->affiliate_tier_rate,
                'viral_tier' => $qualification->viral_tier,
                'qvv' => $volumeSnapshot?->qualifying_viral_volume,
                'reasons' => $qualification->reasons,
            ],
        ];
    }

    private function getActivePlan(Company $company, Carbon $date): CompensationPlan
    {
        $plan = CompensationPlan::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->where('effective_from', '<=', $date->toDateString())
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', $date->toDateString());
            })
            ->orderByDesc('effective_from')
            ->first();

        if (! $plan) {
            throw new \RuntimeException("No active compensation plan found for company {$company->slug} on {$date->toDateString()}");
        }

        return $plan;
    }

    private function deleteExistingRun(Company $company, Carbon $date): void
    {
        $existingRun = CommissionRun::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->whereDate('run_date', $date->toDateString())
            ->first();

        if ($existingRun) {
            // Delete wallet movements that reference this run's ledger entries
            $ledgerEntryIds = CommissionLedgerEntry::withoutGlobalScopes()
                ->where('commission_run_id', $existingRun->id)
                ->pluck('id');

            if ($ledgerEntryIds->isNotEmpty()) {
                WalletMovement::withoutGlobalScopes()
                    ->where('reference_type', 'commission_ledger_entry')
                    ->whereIn('reference_id', $ledgerEntryIds)
                    ->delete();
            }

            // Delete ledger entries, then the run
            CommissionLedgerEntry::withoutGlobalScopes()
                ->where('commission_run_id', $existingRun->id)
                ->delete();

            $existingRun->delete();
        }
    }

    private function writeCapAdjustmentEntries(array $capResult, CommissionRun $run, Company $company): void
    {
        if ($capResult['viral_cap_triggered']) {
            CommissionLedgerEntry::create([
                'company_id' => $company->id,
                'commission_run_id' => $run->id,
                'user_id' => $run->id, // System-level entry; we'll use 0 or first affiliate
                'type' => 'cap_adjustment',
                'amount' => 0,
                'description' => sprintf(
                    'Viral cap triggered: %.4f%% reduction applied',
                    (float) $capResult['viral_reduction_pct'] * 100
                ),
                'qualification_snapshot' => [
                    'cap_type' => 'viral',
                    'reduction_pct' => $capResult['viral_reduction_pct'],
                    'rolling_30d_volume' => $capResult['rolling_30d_volume'],
                ],
                'created_at' => now(),
            ]);
        }

        if ($capResult['global_cap_triggered']) {
            CommissionLedgerEntry::create([
                'company_id' => $company->id,
                'commission_run_id' => $run->id,
                'user_id' => $run->id,
                'type' => 'cap_adjustment',
                'amount' => 0,
                'description' => sprintf(
                    'Global cap triggered: %.4f%% reduction applied',
                    (float) $capResult['global_reduction_pct'] * 100
                ),
                'qualification_snapshot' => [
                    'cap_type' => 'global',
                    'reduction_pct' => $capResult['global_reduction_pct'],
                    'rolling_30d_volume' => $capResult['rolling_30d_volume'],
                ],
                'created_at' => now(),
            ]);
        }
    }
}
