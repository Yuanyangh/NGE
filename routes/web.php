<?php

use App\Http\Controllers\Affiliate\AffiliateLoginController;
use App\Http\Controllers\Affiliate\CommissionsController;
use App\Http\Controllers\Affiliate\DashboardController;
use App\Http\Controllers\Affiliate\TeamController;
use App\Http\Controllers\Affiliate\WalletController;
use App\Http\Middleware\EnsureAffiliate;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Affiliate auth (scoped to company)
Route::get('/{company}/affiliate/login', [AffiliateLoginController::class, 'showLoginForm'])->name('affiliate.login');
Route::post('/{company}/affiliate/login', [AffiliateLoginController::class, 'login'])->middleware('throttle:6,1')->name('affiliate.login.submit');
Route::post('/affiliate/logout', [AffiliateLoginController::class, 'logout'])->middleware('auth')->name('affiliate.logout');

// Affiliate dashboard (authenticated, scoped to company)
Route::middleware(['auth', EnsureAffiliate::class, ResolveTenant::class])
    ->prefix('{company}/affiliate')
    ->name('affiliate.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/team', [TeamController::class, 'index'])->name('team');
        Route::get('/commissions', [CommissionsController::class, 'index'])->name('commissions');
        Route::get('/wallet', [WalletController::class, 'index'])->name('wallet');
    });
