<?php

namespace App\Jobs;

use App\DTOs\PlanConfig;
use App\DTOs\SimulationConfig;
use App\Models\CompensationPlan;
use App\Models\Company;
use App\Models\SimulationRun;
use App\Scopes\CompanyScope;
use App\Services\Simulator\SimulatorOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class RunSimulationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 minutes max
    public int $tries = 1;     // Don't retry — simulations are expensive

    public function __construct(
        public readonly int $simulationRunId,
    ) {}

    public function middleware(): array
    {
        // Prevent duplicate runs for the same simulation
        return [new WithoutOverlapping($this->simulationRunId)];
    }

    public function handle(SimulatorOrchestrator $orchestrator): void
    {
        $run = SimulationRun::withoutGlobalScope(CompanyScope::class)
            ->findOrFail($this->simulationRunId);

        // Guard: only process pending runs
        if ($run->status !== 'pending') {
            return;
        }

        $run->update([
            'status' => 'running',
            'started_at' => now(),
            'progress' => 0,
        ]);

        try {
            $company = Company::findOrFail($run->company_id);
            $plan = CompensationPlan::withoutGlobalScope(CompanyScope::class)
                ->findOrFail($run->compensation_plan_id);

            $config = SimulationConfig::fromArray($run->config);
            $planConfig = PlanConfig::fromArray($plan->config);

            // Seed RNG for reproducibility
            mt_srand($config->seed);

            // Use the execute method directly (we already have a run record)
            // Pass a progress callback that updates the run every 5 days
            $result = $orchestrator->execute($config, $planConfig, function (int $day, int $totalDays) use ($run) {
                // Update progress every 5 days or on last day to avoid too many DB writes
                if ($day % 5 === 0 || $day === $totalDays) {
                    $progress = (int) round(($day / $totalDays) * 100);
                    $run->update(['progress' => $progress]);
                }
            });

            mt_srand();

            $run->update([
                'status' => 'completed',
                'results' => $result->toStorableArray(),
                'progress' => 100,
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            mt_srand();

            $run->update([
                'status' => 'failed',
                'progress' => 0,
                'completed_at' => now(),
            ]);

            throw $e; // Let the queue system handle the failure
        }
    }
}
