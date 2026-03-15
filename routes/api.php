<?php

use App\Http\Controllers\Api\CommissionHistoryController;
use App\Http\Controllers\Api\CommissionRunController;
use App\Http\Controllers\Api\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/companies/{company}/commission-runs', [CommissionRunController::class, 'store']);
    Route::get('/companies/{company}/commission-runs/{run}', [CommissionRunController::class, 'show']);
    Route::get('/users/{user}/wallet', [WalletController::class, 'show']);
    Route::get('/users/{user}/commissions', [CommissionHistoryController::class, 'show']);
});
