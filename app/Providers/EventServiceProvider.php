<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events;
use App\Listeners;
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
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        Events\Pages\PageCreated::class => [
            GenerateNewRoutesFileFromDatabase::class,
        ],
        Events\Pages\PageUpdated::class => [
            GenerateNewRoutesFileFromDatabase::class,
        ],
        Events\DataRefreshRequested::class => [],
        Events\Domains\DomainCreated::class => [
            Listeners\Domains\UpdateDomainToUseCloudflareDns::class,
        ],
        Events\Domains\DnsRecordVerified::class => [

        ],
        Events\Domains\NameServerRecordVerified::class => [

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
