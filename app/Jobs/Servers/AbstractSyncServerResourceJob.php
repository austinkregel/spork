<?php

declare(strict_types=1);

namespace App\Jobs\Servers;

use App\Contracts\Services\ServerServiceContract;
use App\Jobs\AbstractSyncResourceJob;
use App\Services\Factories\ServerServiceFactory;

abstract class AbstractSyncServerResourceJob extends AbstractSyncResourceJob
{
    protected ServerServiceContract $service;

    public function handle(ServerServiceFactory $serviceFactory): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }
        $this->service = $serviceFactory->make($this->credential);
        $this->sync();
    }
}
