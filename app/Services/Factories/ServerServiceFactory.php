<?php

namespace App\Services\Factories;

use App\Jobs\Servers\LinodeSyncJob;
use App\Jobs\Servers\OvhCloudSyncJob;
use App\Jobs\Servers\VultrSyncJob;
use App\Models\Credential;
use App\Services\Server\DigitalOceanService;

class ServerServiceFactory
{
    public function make(Credential $credential)
    {
        return match ($credential->service) {
            Credential::DIGITAL_OCEAN => new DigitalOceanService($credential),
            Credential::OVH_CLOUD => new OvhCloudSyncJob($credential),
            Credential::VULTR => new VultrSyncJob($credential),
            Credential::LINODE => new LinodeSyncJob($credential),
        };
    }
}
