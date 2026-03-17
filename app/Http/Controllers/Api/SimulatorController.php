<?php

namespace App\Http\Controllers\Api;

use App\DTOs\SimulationConfig;
use App\Http\Controllers\Controller;
use App\Scopes\CompanyScope;
use App\Models\CompensationPlan;
use App\Models\Company;
use App\Models\SimulationRun;
use App\Services\Simulator\SimulatorOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SimulatorController extends Controller
{
    public function store(Request $request, Company $company): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'compensation_plan_id' => 'required|exists:compensation_plans,id',
            'projection_days' => 'required|integer|min:1|max:365',
            'starting_affiliates' => 'required|integer|min:1',
            'starting_customers' => 'required|integer|min:0',
            'seed' => 'sometimes|integer',
            'growth' => 'required|array',
            'transactions' => 'required|array',
            'retention' => 'required|array',
            'tree_shape' => 'required|array',
        ]);

        $plan = CompensationPlan::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $company->id)
            ->findOrFail($request->input('compensation_plan_id'));

        $configData = $request->only([
            'projection_days', 'starting_affiliates', 'starting_customers',
            'seed', 'growth', 'transactions', 'retention', 'tree_shape',
        ]);

        $config = SimulationConfig::fromArray($configData);
        $name = $request->input('name', 'API Simulation');

        $orchestrator = app(SimulatorOrchestrator::class);
        $result = $orchestrator->run($company, $plan, $config, $name);

        return response()->json(['data' => $result->toStorableArray()], 201);
    }

    public function index(Company $company): JsonResponse
    {
        $runs = SimulationRun::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $company->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'data' => $runs->map(fn (SimulationRun $run) => [
                'id' => $run->id,
                'name' => $run->name,
                'projection_days' => $run->projection_days,
                'status' => $run->status,
                'created_at' => $run->created_at?->toIso8601String(),
                'completed_at' => $run->completed_at?->toIso8601String(),
            ]),
        ]);
    }

    public function show(Company $company, SimulationRun $simulation): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $simulation->id,
                'name' => $simulation->name,
                'config' => $simulation->config,
                'results' => $simulation->results,
                'projection_days' => $simulation->projection_days,
                'status' => $simulation->status,
                'started_at' => $simulation->started_at?->toIso8601String(),
                'completed_at' => $simulation->completed_at?->toIso8601String(),
                'created_at' => $simulation->created_at?->toIso8601String(),
            ],
        ]);
    }

    public function exportCsv(Company $company, SimulationRun $simulation): StreamedResponse
    {
        $results = $simulation->results;

        return response()->streamDownload(function () use ($results, $simulation) {
            $handle = fopen('php://output', 'w');

            // Header
            fputcsv($handle, [
                'Day', 'Date', 'Total Affiliates', 'Total Customers', 'Active Customers',
                'Daily Volume', 'Rolling 30d Volume', 'Affiliate Commissions',
                'Viral Commissions', 'Total Payout', 'Payout Ratio %',
                'Viral Cap Applied', 'Global Cap Applied',
            ]);

            // Daily projections
            foreach ($results['daily_projections'] ?? [] as $dp) {
                fputcsv($handle, [
                    $dp['day'], $dp['date'], $dp['total_affiliates'], $dp['total_customers'],
                    $dp['active_customers'], $dp['daily_volume'], $dp['rolling_30d_volume'],
                    $dp['affiliate_commissions'], $dp['viral_commissions'], $dp['total_payout'],
                    $dp['payout_ratio_percent'], $dp['viral_cap_applied'] ? 'Yes' : 'No',
                    $dp['global_cap_applied'] ? 'Yes' : 'No',
                ]);
            }

            fclose($handle);
        }, "simulation-{$simulation->id}-{$simulation->name}.csv", [
            'Content-Type' => 'text/csv',
        ]);
    }
}
