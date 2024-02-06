<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Repositories\CredentialRepositoryContract;
use App\Contracts\Services\ImapServiceContract;
use App\Contracts\Services\PlaidServiceContract;
use App\Contracts\Services\WeatherServiceContract;
use App\Operations\Operator;
use App\Repositories\CredentialRepository;
use App\Services\Finance\PlaidService;
use App\Services\Messaging\ImapCredentialService;
use App\Services\Weather\OpenWeatherService;
use App\Services\Weather\WeatherApiService;
use App\Services\Weather\WeatherGovApiService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PlaidServiceContract::class, PlaidService::class);
        $this->app->bind(CredentialRepositoryContract::class, CredentialRepository::class);
        $this->app->bind(ImapServiceContract::class, ImapCredentialService::class);
        $this->app->bind(WeatherServiceContract::class, OpenWeatherService::class);
        $this->app->alias(Operator::class, 'operator');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
