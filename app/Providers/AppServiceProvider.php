<?php

namespace App\Providers;

use App\Events\TransactionRefunded;
use App\Listeners\ProcessRefundClawback;
use App\Models\CompensationPlan;
use App\Scopes\CompanyScope;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(TransactionRefunded::class, ProcessRefundClawback::class);

        // Bypass CompanyScope for implicit route model binding in admin panel
        // (admin users access all companies, so the global scope blocks resolution)
        Route::bind('compensationPlan', function (string $value) {
            return CompensationPlan::withoutGlobalScope(CompanyScope::class)
                ->findOrFail($value);
        });
    }
}
