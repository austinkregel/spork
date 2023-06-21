<?php

namespace App\Jobs;

use App\Contracts\Services\DomainServiceContract;
use App\Jobs\AbstractSyncResourceJob;
use App\Services\Factories\DomainServiceFactory;

abstract class AbstractSyncDomainResource extends AbstractSyncResourceJob
{
    protected DomainServiceContract $service;

    public function handle(DomainServiceFactory $domainServiceFactory)
    {
        $this->service = $domainServiceFactory->make($this->credential);
        if ($this->batch()?->cancelled()) {
            return;
        }
        $this->sync();
    }
}
