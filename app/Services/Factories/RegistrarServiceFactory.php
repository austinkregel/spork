<?php

namespace App\Services\Factories;

use App\Contracts\Services\RegistrarServiceContract;
use App\Models\Credential;
use App\Services\Registrar\CloudflareRegistrarService;
use App\Services\Registrar\NamecheapService;

class RegistrarServiceFactory
{
    public function make(Credential $credential): RegistrarServiceContract
    {
        return match ($credential->service) {
            Credential::NAMECHEAP => new NamecheapService($credential),
            Credential::CLOUDFLARE => new CloudflareRegistrarService($credential),
        };
    }
}

/*
 * Plans can be capped based on disk size, disk size could be monitored every couple of minutes for a given directory,
 *  or it could update based on file watches or something
 *      The point being to monitor disk usage via cron or monitoring an S3 folder or something.
 *      Then actions performed by their user could be restricted when a limit is reached.
 *      We would likely want to suspend any active session that user has to a server.
 *
 */
