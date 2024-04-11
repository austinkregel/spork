<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Repositories\CredentialRepositoryContract;
use App\Contracts\Services\ImapServiceContract;
use App\Contracts\Services\JiraServiceContract;
use App\Contracts\Services\NamecheapServiceContract;
use App\Contracts\Services\PlaidServiceContract;
use App\Contracts\Services\WeatherServiceContract;
use App\Models\Credential;
use App\Models\Domain;
use App\Models\Navigation;
use App\Models\Page;
use App\Models\Person;
use App\Models\Project;
use App\Models\Research;
use App\Models\Server;
use App\Models\Spork\Script;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Observers\ApplyCredentialsObserver;
use App\Operations\Operator;
use App\Repositories\CredentialRepository;
use App\Services\Finance\PlaidService;
use App\Services\JiraService;
use App\Services\Messaging\ImapCredentialService;
use App\Services\Registrar\NamecheapService;
use App\Services\Weather\OpenWeatherService;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Credential::observe(ApplyCredentialsObserver::class);
        Domain::observe(ApplyCredentialsObserver::class);
        Navigation::observe(ApplyCredentialsObserver::class);
        Page::observe(ApplyCredentialsObserver::class);
        Person::observe(ApplyCredentialsObserver::class);
        Project::observe(ApplyCredentialsObserver::class);
        Research::observe(ApplyCredentialsObserver::class);
        Role::observe(ApplyCredentialsObserver::class);
        Script::observe(ApplyCredentialsObserver::class);
        Server::observe(ApplyCredentialsObserver::class);
        Task::observe(ApplyCredentialsObserver::class);
        Team::observe(ApplyCredentialsObserver::class);
        User::observe(ApplyCredentialsObserver::class);

        $this->app->bind(NamecheapServiceContract::class, NamecheapService::class);
        $this->app->bind(PlaidServiceContract::class, PlaidService::class);
        $this->app->bind(CredentialRepositoryContract::class, CredentialRepository::class);
        $this->app->bind(ImapServiceContract::class, ImapCredentialService::class);
        $this->app->bind(WeatherServiceContract::class, OpenWeatherService::class);
        $this->app->bind(JiraServiceContract::class, JiraService::class);
        $this->app->alias(Operator::class, 'operator');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

    }
}
