<?php

namespace App\Services\Simulator;

use App\DTOs\PlanConfig;
use App\DTOs\SimulationConfig;
use App\DTOs\SimulationResult;
use App\Models\CompensationPlan;
use App\Models\Company;
use App\Models\SimulationRun;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SimulatorOrchestrator
{
    public function __construct(
        private readonly NetworkGrowthProjector $networkProjector,
        private readonly TransactionProjector $transactionProjector,
        private readonly PayoutProjector $payoutProjector,
        private readonly SimulatorReportBuilder $reportBuilder,
    ) {}

    public function run(
        Company $company,
        CompensationPlan $plan,
        SimulationConfig $config,
        string $name = 'Simulation',
    ): SimulationResult {
        // Seed RNG for reproducibility
        mt_srand($config->seed);

        $planConfig = PlanConfig::fromArray($plan->config);

        // Create simulation run record
        $simulationRun = SimulationRun::create([
            'company_id' => $company->id,
            'compensation_plan_id' => $plan->id,
            'name' => $name,
            'config' => $config->toNestedArray(),
            'projection_days' => $config->projection_days,
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $result = $this->execute($config, $planConfig);

            $simulationRun->update([
                'status' => 'completed',
                'results' => $result->toStorableArray(),
                'completed_at' => now(),
            ]);

            // Reset RNG to not affect other code
            mt_srand();

            return $result;
        } catch (\Throwable $e) {
            $simulationRun->update([
                'status' => 'failed',
                'completed_at' => now(),
            ]);

            mt_srand();

            throw $e;
        }
    }

    /**
     * Execute simulation without persisting (for testing or async job use).
     * The optional $onProgress callback receives ($day, $totalDays) after each day is processed.
     */
    public function execute(SimulationConfig $config, PlanConfig $planConfig, ?callable $onProgress = null): SimulationResult
    {
        $network = $this->networkProjector->initializeNetwork($config);
        $allTransactions = []; // plain array for efficient append + prune
        $dailyProjections = [];
        $startDate = Carbon::today()->addDay();

        // Track rolling commission totals (keyed by date for 30-day window)
        $dailyViralTotals = [];
        $dailyAllTotals = [];

        // Track per-affiliate cumulative earnings
        $affiliateEarnings = [];

        // Track tier distribution
        $tierAccumulator = [
            'affiliate_tiers' => [],
            'viral_tiers' => [],
        ];

        // Build sponsor tree once from initial network, then update incrementally
        $sponsorTree = [];
        foreach ($network as $user) {
            if ($user['sponsor_id'] !== null) {
                $sponsorTree[$user['sponsor_id']][] = $user['id'];
            }
        }

        $previousNetworkSize = $network->count();

        for ($day = 1; $day <= $config->projection_days; $day++) {
            $currentDate = $startDate->copy()->addDays($day - 1);
            $dateString = $currentDate->toDateString();

            // 1. Grow the network
            $network = $this->networkProjector->projectDay($network, $config, $day);

            // Incrementally update sponsor tree with new users
            $currentNetworkSize = $network->count();
            for ($i = $previousNetworkSize; $i < $currentNetworkSize; $i++) {
                $user = $network[$i];
                if ($user['sponsor_id'] !== null) {
                    $sponsorTree[$user['sponsor_id']][] = $user['id'];
                }
            }
            $previousNetworkSize = $currentNetworkSize;

            // 2. Generate synthetic transactions
            $todayTransactions = $this->transactionProjector->projectDay(
                $network, $config, $day, $dateString
            );

            // Append today's transactions to the running array
            foreach ($todayTransactions as $txn) {
                $allTransactions[] = $txn;
            }

            // Prune transactions older than rolling window to save memory
            $windowCutoffStr = $currentDate->copy()->subDays($planConfig->rolling_days)->toDateString();
            $allTransactions = array_values(array_filter(
                $allTransactions,
                fn (array $t) => $t['transaction_date'] >= $windowCutoffStr
            ));

            // Calculate rolling 30d paid commissions
            $rollingViralPaid = $this->sumRollingTotals($dailyViralTotals, $currentDate, $planConfig->rolling_days);
            $rollingAllPaid = $this->sumRollingTotals($dailyAllTotals, $currentDate, $planConfig->rolling_days);

            // 3. Run commission calculations — pass pre-built sponsorTree
            $payoutResult = $this->payoutProjector->projectDay(
                network: $network,
                todayTransactions: $todayTransactions,
                allTransactions: collect($allTransactions),
                planConfig: $planConfig,
                dateString: $dateString,
                day: $day,
                rollingViralPaid: $rollingViralPaid,
                rollingAllPaid: $rollingAllPaid,
                sponsorTree: $sponsorTree,
            );

            // 4. Record day projection
            $dailyProjections[] = $payoutResult['day_projection'];

            // Track rolling daily commissions
            $dayViral = '0';
            $dayAll = '0';
            foreach ($payoutResult['adjusted_results'] as $result) {
                $dayViral = bcadd($dayViral, $result['viral_commission'], 4);
                $dayAll = bcadd($dayAll, bcadd($result['affiliate_commission'], $result['viral_commission'], 4), 4);

                // Accumulate per-affiliate earnings
                $uid = $result['user_id'];
                if (!isset($affiliateEarnings[$uid])) {
                    $affiliateEarnings[$uid] = ['affiliate' => '0', 'viral' => '0'];
                }
                $affiliateEarnings[$uid]['affiliate'] = bcadd(
                    $affiliateEarnings[$uid]['affiliate'], $result['affiliate_commission'], 4
                );
                $affiliateEarnings[$uid]['viral'] = bcadd(
                    $affiliateEarnings[$uid]['viral'], $result['viral_commission'], 4
                );
            }

            $dailyViralTotals[$dateString] = $dayViral;
            $dailyAllTotals[$dateString] = $dayAll;

            // Accumulate tier distributions with actual paid amounts
            // Build a lookup from adjusted_results for per-affiliate commissions
            $adjustedByUser = [];
            foreach ($payoutResult['adjusted_results'] as $r) {
                $adjustedByUser[$r['user_id']] = $r;
            }

            foreach ($payoutResult['per_affiliate'] as $uid => $info) {
                $adjusted = $adjustedByUser[$uid] ?? null;

                if ($info['affiliate_tier_index'] !== null) {
                    $rate = (string) $info['affiliate_tier_rate'];
                    if (!isset($tierAccumulator['affiliate_tiers'][$rate])) {
                        $tierAccumulator['affiliate_tiers'][$rate] = [
                            'tier_rate' => (float) $rate,
                            'affiliate_count' => 0,
                            'total_paid' => '0',
                        ];
                    }
                    $tierAccumulator['affiliate_tiers'][$rate]['affiliate_count']++;
                    if ($adjusted) {
                        $tierAccumulator['affiliate_tiers'][$rate]['total_paid'] = bcadd(
                            $tierAccumulator['affiliate_tiers'][$rate]['total_paid'],
                            $adjusted['affiliate_commission'],
                            4
                        );
                    }
                }

                if ($info['viral_tier'] !== null) {
                    $tier = $info['viral_tier'];
                    if (!isset($tierAccumulator['viral_tiers'][$tier])) {
                        $tierAccumulator['viral_tiers'][$tier] = [
                            'tier' => $tier,
                            'affiliate_count' => 0,
                            'total_paid' => '0',
                        ];
                    }
                    $tierAccumulator['viral_tiers'][$tier]['affiliate_count']++;
                    if ($adjusted) {
                        $tierAccumulator['viral_tiers'][$tier]['total_paid'] = bcadd(
                            $tierAccumulator['viral_tiers'][$tier]['total_paid'],
                            $adjusted['viral_commission'],
                            4
                        );
                    }
                }
            }

            // Invoke progress callback after all processing for this day
            if ($onProgress !== null) {
                $onProgress($day, $config->projection_days);
            }
        }

        // Normalize tier accumulators to arrays
        $tierAccumulator['affiliate_tiers'] = array_values($tierAccumulator['affiliate_tiers']);
        $tierAccumulator['viral_tiers'] = array_values($tierAccumulator['viral_tiers']);

        // 5. Compile report
        return $this->reportBuilder->build(
            $dailyProjections,
            $config,
            $affiliateEarnings,
            $tierAccumulator,
        );
    }

    private function sumRollingTotals(array $dailyTotals, Carbon $currentDate, int $rollingDays): string
    {
        $windowStart = $currentDate->copy()->subDays($rollingDays - 1)->toDateString();
        $yesterday = $currentDate->copy()->subDay()->toDateString();

        $sum = '0';
        foreach ($dailyTotals as $date => $amount) {
            if ($date >= $windowStart && $date <= $yesterday) {
                $sum = bcadd($sum, $amount, 4);
            }
        }

        return $sum;
    }
}
