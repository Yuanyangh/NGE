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

// Company slug validation (used by landing page modal)
Route::get('/api/company/{slug}/check', function (string $slug) {
    $company = \App\Models\Company::whereRaw('LOWER(slug) = ?', [strtolower($slug)])->first();
    if ($company) {
        return response()->json(['exists' => true, 'slug' => $company->slug]);
    }
    return response()->json(['exists' => false], 404);
});

// Affiliate auth (scoped to company)
Route::get('/{companySlug}/affiliate/login', [AffiliateLoginController::class, 'showLoginForm'])
    ->name('affiliate.login');
Route::post('/{companySlug}/affiliate/login', [AffiliateLoginController::class, 'login'])
    ->middleware('throttle:6,1')
    ->name('affiliate.login.submit');
Route::post('/affiliate/logout', [AffiliateLoginController::class, 'logout'])->middleware('auth')->name('affiliate.logout');

// Affiliate dashboard (authenticated, scoped to company)
Route::middleware(['auth', EnsureAffiliate::class, ResolveTenant::class])
    ->prefix('{company}/affiliate')
    ->name('affiliate.')
    ->missing(fn () => response()->view('affiliate.auth.login', ['company' => null, 'companyError' => 'The company you\'re looking for doesn\'t exist. Please check the URL and try again.'], 404))
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/team', [TeamController::class, 'index'])->name('team');
        Route::get('/commissions', [CommissionsController::class, 'index'])->name('commissions');
        Route::get('/wallet', [WalletController::class, 'index'])->name('wallet');
    });
