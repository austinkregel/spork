<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Services\PlaidServiceContract;
use App\Services\Finance\PlaidService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PlaidServiceContract::class, PlaidService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
