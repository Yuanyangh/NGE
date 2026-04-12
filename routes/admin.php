<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BonusTypeController;
use App\Http\Controllers\Admin\CommissionLedgerController;
use App\Http\Controllers\Admin\CommissionRunController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\CompensationPlanController;
use App\Http\Controllers\Admin\ComplianceController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\IncomeDisclosureController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WalletController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->prefix('admin')->name('admin.')->group(function () {
    // Auth (guest only)
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login']);
    });

    Route::post('logout', [AuthController::class, 'logout'])
        ->middleware('auth')
        ->name('logout');

    // Protected routes
    Route::middleware(['auth', EnsureAdmin::class])->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');

        // Companies
        Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
        Route::get('companies/create', [CompanyController::class, 'create'])->name('companies.create');
        Route::post('companies', [CompanyController::class, 'store'])->name('companies.store');
        Route::get('companies/{company}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
        Route::put('companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
        Route::delete('companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');

        // Users (cross-company)
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // Compensation Plans (cross-company)
        Route::get('compensation-plans', [CompensationPlanController::class, 'index'])->name('compensation-plans.index');
        Route::get('compensation-plans/create', [CompensationPlanController::class, 'create'])->name('compensation-plans.create');
        Route::post('compensation-plans', [CompensationPlanController::class, 'store'])->name('compensation-plans.store');
        Route::get('compensation-plans/{compensationPlan}/edit', [CompensationPlanController::class, 'edit'])->name('compensation-plans.edit');
        Route::put('compensation-plans/{compensationPlan}', [CompensationPlanController::class, 'update'])->name('compensation-plans.update');
        Route::delete('compensation-plans/{compensationPlan}', [CompensationPlanController::class, 'destroy'])->name('compensation-plans.destroy');

        // Commission Runs (read-only + trigger)
        Route::get('commission-runs', [CommissionRunController::class, 'index'])->name('commission-runs.index');
        Route::get('commission-runs/{id}', [CommissionRunController::class, 'show'])->name('commission-runs.show');
        Route::post('commission-runs/trigger', [CommissionRunController::class, 'trigger'])->name('commission-runs.trigger');

        // Commission Ledger (read-only)
        Route::get('commission-ledger', [CommissionLedgerController::class, 'index'])->name('commission-ledger.index');

        // Wallet Accounts (read-only)
        Route::get('wallets', [WalletController::class, 'index'])->name('wallets.index');
        Route::get('wallets/{id}', [WalletController::class, 'show'])->name('wallets.show');

        // Simulator
        Route::get('simulator', \App\Livewire\Admin\Pages\ScenarioSimulator::class)->name('simulator');

        // Reports
        Route::get('companies/{company}/reports/income-disclosure', [IncomeDisclosureController::class, 'index'])
            ->name('companies.reports.income-disclosure');

        // Compliance
        Route::get('companies/{company}/compliance', [ComplianceController::class, 'index'])
            ->name('companies.compliance');

        // Bonus Types (nested under company + plan)
        Route::get('companies/{company}/plans/{compensationPlan}/bonus-types', [BonusTypeController::class, 'index'])->name('companies.plans.bonus-types.index');
        Route::get('companies/{company}/plans/{compensationPlan}/bonus-types/create', [BonusTypeController::class, 'create'])->name('companies.plans.bonus-types.create');
        Route::post('companies/{company}/plans/{compensationPlan}/bonus-types', [BonusTypeController::class, 'store'])->name('companies.plans.bonus-types.store');
        Route::get('companies/{company}/plans/{compensationPlan}/bonus-types/{bonusType}/edit', [BonusTypeController::class, 'edit'])->name('companies.plans.bonus-types.edit');
        Route::put('companies/{company}/plans/{compensationPlan}/bonus-types/{bonusType}', [BonusTypeController::class, 'update'])->name('companies.plans.bonus-types.update');
        Route::delete('companies/{company}/plans/{compensationPlan}/bonus-types/{bonusType}', [BonusTypeController::class, 'destroy'])->name('companies.plans.bonus-types.destroy');
        Route::post('companies/{company}/plans/{compensationPlan}/bonus-types/{bonusType}/toggle', [BonusTypeController::class, 'toggleActive'])->name('bonus-types.toggle');
    });
});
