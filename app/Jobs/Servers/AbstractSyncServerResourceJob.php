<?php

namespace App\Jobs\Servers;

use App\Contracts\Services\ServerServiceContract;
use App\Jobs\AbstractSyncResourceJob;
use App\Models\Credential;
use App\Models\Server;
use App\Services\Factories\ServerServiceFactory;

abstract class AbstractSyncServerResourceJob extends AbstractSyncResourceJob
{
    protected ServerServiceContract $service;

    public function handle(ServerServiceFactory $serviceFactory)
    {
        $this->service = $serviceFactory->make($this->credential);
        $this->sync();
    }
}
