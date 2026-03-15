<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommissionRun;
use App\Models\Company;
use App\Services\Commission\CommissionRunOrchestrator;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommissionRunController extends Controller
{
    public function store(Request $request, Company $company): JsonResponse
    {
        $request->validate([
            'date' => 'sometimes|date',
        ]);

        $date = $request->has('date')
            ? Carbon::parse($request->input('date'))
            : Carbon::today();

        $orchestrator = app(CommissionRunOrchestrator::class);
        $run = $orchestrator->run($company, $date);

        return response()->json([
            'data' => [
                'id' => $run->id,
                'company_id' => $run->company_id,
                'run_date' => $run->run_date->toDateString(),
                'status' => $run->status,
                'total_affiliate_commission' => $run->total_affiliate_commission,
                'total_viral_commission' => $run->total_viral_commission,
                'total_company_volume' => $run->total_company_volume,
                'viral_cap_triggered' => $run->viral_cap_triggered,
            ],
        ], 201);
    }

    public function show(Company $company, CommissionRun $run): JsonResponse
    {
        $run->load('ledgerEntries');

        return response()->json([
            'data' => [
                'id' => $run->id,
                'company_id' => $run->company_id,
                'run_date' => $run->run_date->toDateString(),
                'status' => $run->status,
                'total_affiliate_commission' => $run->total_affiliate_commission,
                'total_viral_commission' => $run->total_viral_commission,
                'total_company_volume' => $run->total_company_volume,
                'viral_cap_triggered' => $run->viral_cap_triggered,
                'started_at' => $run->started_at,
                'completed_at' => $run->completed_at,
                'ledger_entries' => $run->ledgerEntries->map(fn ($entry) => [
                    'id' => $entry->id,
                    'user_id' => $entry->user_id,
                    'type' => $entry->type,
                    'amount' => $entry->amount,
                    'tier_achieved' => $entry->tier_achieved,
                    'description' => $entry->description,
                ]),
            ],
        ]);
    }
}
