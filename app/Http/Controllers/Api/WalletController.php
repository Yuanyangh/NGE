<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalletAccount;
use App\Models\WalletMovement;
use Illuminate\Http\JsonResponse;

class WalletController extends Controller
{
    public function show(User $user): JsonResponse
    {
        $wallet = WalletAccount::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->first();

        if (! $wallet) {
            return response()->json([
                'data' => [
                    'user_id' => $user->id,
                    'balance' => '0.0000',
                    'currency' => $user->company->currency ?? 'USD',
                    'movements' => [],
                ],
            ]);
        }

        $movements = WalletMovement::withoutGlobalScopes()
            ->where('wallet_account_id', $wallet->id)
            ->orderByDesc('effective_at')
            ->limit(50)
            ->get();

        return response()->json([
            'data' => [
                'user_id' => $user->id,
                'balance' => $wallet->balance(),
                'currency' => $wallet->currency,
                'movements' => $movements->map(fn ($m) => [
                    'id' => $m->id,
                    'type' => $m->type,
                    'amount' => $m->amount,
                    'status' => $m->status,
                    'description' => $m->description,
                    'effective_at' => $m->effective_at?->toIso8601String(),
                ]),
            ],
        ]);
    }
}
