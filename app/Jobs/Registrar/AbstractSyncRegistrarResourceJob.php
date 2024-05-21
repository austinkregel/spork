<?php

declare(strict_types=1);

namespace App\Jobs\Registrar;

use App\Contracts\Services\RegistrarServiceContract;
use App\Jobs\AbstractSyncResourceJob;
use App\Services\Factories\RegistrarServiceFactory;

abstract class AbstractSyncRegistrarResourceJob extends AbstractSyncResourceJob
{
    protected RegistrarServiceContract $service;

    public function handle(RegistrarServiceFactory $registrarServiceFactory): void
    {
        $this->service = $registrarServiceFactory->make($this->credential);
        $this->sync();
    }
}
