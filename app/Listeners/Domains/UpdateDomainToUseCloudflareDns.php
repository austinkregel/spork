<?php

declare(strict_types=1);

namespace App\Listeners\Domains;

use App\Events\Domains\DomainCreated;
use App\Jobs\Deployment\Steps\SetupCloudflareDns;

class UpdateDomainToUseCloudflareDns
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(DomainCreated $event): void
    {
        dispatch_sync(new SetupCloudflareDns(
            $event->domain,
            $event->cloudflareCredentials,
            $event->registrar,
        ));
    }
}
