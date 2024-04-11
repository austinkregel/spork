<?php

declare(strict_types=1);

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Models\Credential;
use App\Models\Domain;
use App\Models\Navigation;
use App\Models\Page;
use App\Models\Person;
use App\Models\Project;
use App\Models\Research;
use App\Models\Server;
use App\Models\Spork\Script;
use App\Models\Tag;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Policies\CredentialPolicy;
use App\Policies\DomainPolicy;
use App\Policies\NavigationPolicy;
use App\Policies\PagePolicy;
use App\Policies\PersonPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\ResearchPolicy;
use App\Policies\RolePolicy;
use App\Policies\ScriptPolicy;
use App\Policies\ServerPolicy;
use App\Policies\TagPolicy;
use App\Policies\TaskPolicy;
use App\Policies\TeamPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Spatie\Permission\Models\Role;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Credential::class => CredentialPolicy::class,
        Domain::class => DomainPolicy::class,
        Navigation::class => NavigationPolicy::class,
        Page::class => PagePolicy::class,
        Person::class => PersonPolicy::class,
        Project::class => ProjectPolicy::class,
        Research::class => ResearchPolicy::class,
        Role::class => RolePolicy::class,
        Script::class => ScriptPolicy::class,
        Server::class => ServerPolicy::class,
        Tag::class => TagPolicy::class,
        Task::class => TaskPolicy::class,
        Team::class => TeamPolicy::class,
        User::class => UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
