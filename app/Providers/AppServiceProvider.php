<?php

namespace App\Providers;

use App\Events\TransactionRefunded;
use App\Listeners\ProcessRefundClawback;
use Illuminate\Support\Facades\Event;
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
    }
}
