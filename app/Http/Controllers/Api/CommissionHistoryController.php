<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommissionLedgerEntry;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class CommissionHistoryController extends Controller
{
    public function show(User $user): JsonResponse
    {
        $entries = CommissionLedgerEntry::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->whereIn('type', ['affiliate_commission', 'viral_commission'])
            ->with('commissionRun:id,run_date,status')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => $entries->map(fn ($entry) => [
                'id' => $entry->id,
                'type' => $entry->type,
                'amount' => $entry->amount,
                'tier_achieved' => $entry->tier_achieved,
                'description' => $entry->description,
                'run_date' => $entry->commissionRun?->run_date?->toDateString(),
                'created_at' => $entry->created_at,
            ]),
        ]);
    }
}
