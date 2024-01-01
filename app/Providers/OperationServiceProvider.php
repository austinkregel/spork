<?php

declare(strict_types=1);

namespace App\Providers;

use App\Console\Commands\Operations\MakeOperationCommand;
use App\Console\Commands\Operations\MakeOperationMigrationCommand;
use App\Console\Commands\Operations\MigrationCreator;
use App\Console\Commands\Operations\QueueCommand;
use App\Operations\Operator;
use Illuminate\Support\ServiceProvider;

class OperationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/operations.php' => config_path('operations.php'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                QueueCommand::class,
                MakeOperationCommand::class,
                MakeOperationMigrationCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        if ($this->app->environment() === 'testing') {
            $this->loadMigrationsFrom(base_path('/tests/database'));
        }

        $this->app->alias(Operator::class, 'operator');

        $this->app->when(MigrationCreator::class)
            ->needs('$customStubPath')
            ->give(function ($app) {
                return $app->basePath('stubs');
            });
    }
}
