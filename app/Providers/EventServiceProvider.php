<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events;
use App\Events\Domains\DnsRecordVerified;
use App\Events\Domains\DomainCreated;
use App\Events\Domains\NameServerRecordVerified;
use App\Events\Models\Account\AccountCreated;
use App\Events\Models\Account\AccountCreating;
use App\Events\Models\Account\AccountDeleted;
use App\Events\Models\Account\AccountDeleting;
use App\Events\Models\Account\AccountUpdated;
use App\Events\Models\Account\AccountUpdating;
use App\Events\Models\Article\ArticleCreated;
use App\Events\Models\Article\ArticleCreating;
use App\Events\Models\Article\ArticleDeleted;
use App\Events\Models\Article\ArticleDeleting;
use App\Events\Models\Article\ArticleUpdated;
use App\Events\Models\Article\ArticleUpdating;
use App\Events\Models\Budget\BudgetCreated;
use App\Events\Models\Budget\BudgetCreating;
use App\Events\Models\Budget\BudgetDeleted;
use App\Events\Models\Budget\BudgetDeleting;
use App\Events\Models\Budget\BudgetUpdated;
use App\Events\Models\Budget\BudgetUpdating;
use App\Events\Models\Condition\ConditionCreated;
use App\Events\Models\Condition\ConditionCreating;
use App\Events\Models\Condition\ConditionDeleted;
use App\Events\Models\Condition\ConditionDeleting;
use App\Events\Models\Condition\ConditionUpdated;
use App\Events\Models\Condition\ConditionUpdating;
use App\Events\Models\Credential\CredentialCreated;
use App\Events\Models\Credential\CredentialCreating;
use App\Events\Models\Credential\CredentialDeleted;
use App\Events\Models\Credential\CredentialDeleting;
use App\Events\Models\Credential\CredentialUpdated;
use App\Events\Models\Credential\CredentialUpdating;
use App\Events\Models\Domain\DomainCreating;
use App\Events\Models\Domain\DomainDeleted;
use App\Events\Models\Domain\DomainDeleting;
use App\Events\Models\Domain\DomainUpdated;
use App\Events\Models\Domain\DomainUpdating;
use App\Events\Models\DomainAnalytics\DomainAnalyticsCreated;
use App\Events\Models\DomainAnalytics\DomainAnalyticsCreating;
use App\Events\Models\DomainAnalytics\DomainAnalyticsDeleted;
use App\Events\Models\DomainAnalytics\DomainAnalyticsDeleting;
use App\Events\Models\DomainAnalytics\DomainAnalyticsUpdated;
use App\Events\Models\DomainAnalytics\DomainAnalyticsUpdating;
use App\Events\Models\DomainRecord\DomainRecordCreated;
use App\Events\Models\DomainRecord\DomainRecordCreating;
use App\Events\Models\DomainRecord\DomainRecordDeleted;
use App\Events\Models\DomainRecord\DomainRecordDeleting;
use App\Events\Models\DomainRecord\DomainRecordUpdated;
use App\Events\Models\DomainRecord\DomainRecordUpdating;
use App\Events\Models\ExternalRssFeed\ExternalRssFeedCreated;
use App\Events\Models\ExternalRssFeed\ExternalRssFeedCreating;
use App\Events\Models\ExternalRssFeed\ExternalRssFeedDeleted;
use App\Events\Models\ExternalRssFeed\ExternalRssFeedDeleting;
use App\Events\Models\ExternalRssFeed\ExternalRssFeedUpdated;
use App\Events\Models\ExternalRssFeed\ExternalRssFeedUpdating;
use App\Events\Models\Membership\MembershipCreated;
use App\Events\Models\Membership\MembershipCreating;
use App\Events\Models\Membership\MembershipDeleted;
use App\Events\Models\Membership\MembershipDeleting;
use App\Events\Models\Membership\MembershipUpdated;
use App\Events\Models\Membership\MembershipUpdating;
use App\Events\Models\Message\MessageCreated;
use App\Events\Models\Message\MessageCreating;
use App\Events\Models\Message\MessageDeleted;
use App\Events\Models\Message\MessageDeleting;
use App\Events\Models\Message\MessageUpdated;
use App\Events\Models\Message\MessageUpdating;
use App\Events\Models\Navigation\NavigationCreated;
use App\Events\Models\Navigation\NavigationCreating;
use App\Events\Models\Navigation\NavigationDeleted;
use App\Events\Models\Navigation\NavigationDeleting;
use App\Events\Models\Navigation\NavigationUpdated;
use App\Events\Models\Navigation\NavigationUpdating;
use App\Events\Models\Page\PageCreating;
use App\Events\Models\Page\PageDeleted;
use App\Events\Models\Page\PageDeleting;
use App\Events\Models\Page\PageUpdating;
use App\Events\Models\Person\PersonCreated;
use App\Events\Models\Person\PersonCreating;
use App\Events\Models\Person\PersonDeleted;
use App\Events\Models\Person\PersonDeleting;
use App\Events\Models\Person\PersonUpdated;
use App\Events\Models\Person\PersonUpdating;
use App\Events\Models\Project\ProjectCreated;
use App\Events\Models\Project\ProjectCreating;
use App\Events\Models\Project\ProjectDeleted;
use App\Events\Models\Project\ProjectDeleting;
use App\Events\Models\Project\ProjectUpdated;
use App\Events\Models\Project\ProjectUpdating;
use App\Events\Models\Research\ResearchCreated;
use App\Events\Models\Research\ResearchCreating;
use App\Events\Models\Research\ResearchDeleted;
use App\Events\Models\Research\ResearchDeleting;
use App\Events\Models\Research\ResearchUpdated;
use App\Events\Models\Research\ResearchUpdating;
use App\Events\Models\Script\ScriptCreated;
use App\Events\Models\Script\ScriptCreating;
use App\Events\Models\Script\ScriptDeleted;
use App\Events\Models\Script\ScriptDeleting;
use App\Events\Models\Script\ScriptUpdated;
use App\Events\Models\Script\ScriptUpdating;
use App\Events\Models\Server\ServerCreated;
use App\Events\Models\Server\ServerCreating;
use App\Events\Models\Server\ServerDeleted;
use App\Events\Models\Server\ServerDeleting;
use App\Events\Models\Server\ServerUpdated;
use App\Events\Models\Server\ServerUpdating;
use App\Events\Models\ShortCode\ShortCodeCreated;
use App\Events\Models\ShortCode\ShortCodeCreating;
use App\Events\Models\ShortCode\ShortCodeDeleted;
use App\Events\Models\ShortCode\ShortCodeDeleting;
use App\Events\Models\ShortCode\ShortCodeUpdated;
use App\Events\Models\ShortCode\ShortCodeUpdating;
use App\Events\Models\Tag\TagCreated;
use App\Events\Models\Tag\TagCreating;
use App\Events\Models\Tag\TagDeleted;
use App\Events\Models\Tag\TagDeleting;
use App\Events\Models\Tag\TagUpdated;
use App\Events\Models\Tag\TagUpdating;
use App\Events\Models\Thread\ThreadCreated;
use App\Events\Models\Thread\ThreadCreating;
use App\Events\Models\Thread\ThreadDeleted;
use App\Events\Models\Thread\ThreadDeleting;
use App\Events\Models\Thread\ThreadUpdated;
use App\Events\Models\Thread\ThreadUpdating;
use App\Events\Models\Transaction\TransactionCreated;
use App\Events\Models\Transaction\TransactionCreating;
use App\Events\Models\Transaction\TransactionDeleted;
use App\Events\Models\Transaction\TransactionDeleting;
use App\Events\Models\Transaction\TransactionUpdated;
use App\Events\Models\Transaction\TransactionUpdating;
use App\Events\Models\User\UserCreated;
use App\Events\Models\User\UserCreating;
use App\Events\Models\User\UserDeleted;
use App\Events\Models\User\UserDeleting;
use App\Events\Models\User\UserUpdated;
use App\Events\Models\User\UserUpdating;
use App\Events\Pages\PageCreated;
use App\Events\Pages\PageUpdated;
use App\Listeners;
use App\Listeners\DebugEventListener;
use App\Listeners\Pages\GenerateNewRoutesFileFromDatabase;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        NameServerRecordVerified::class => [
            DebugEventListener::class, // code: this is an autogenerated line
        ],
        DnsRecordVerified::class => [
        ],
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        PageCreated::class => [
            DebugEventListener::class, // code: this is an autogenerated line
            GenerateNewRoutesFileFromDatabase::class,
        ],
        PageUpdated::class => [
            DebugEventListener::class, // code: this is an autogenerated line
            GenerateNewRoutesFileFromDatabase::class,
        ],
        DomainCreated::class => [
            DebugEventListener::class, // code: this is an autogenerated line
            Listeners\Domains\UpdateDomainToUseCloudflareDns::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
