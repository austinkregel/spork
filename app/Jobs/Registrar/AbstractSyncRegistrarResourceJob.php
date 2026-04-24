<?php

declare(strict_types=1);

namespace App\Jobs\Registrar;

use App\Contracts\Services\RegistrarServiceContract;
use App\Jobs\AbstractSyncResourceJob;
use App\Services\Factories\RegistrarServiceFactory;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class AbstractSyncRegistrarResourceJob extends AbstractSyncResourceJob
{
    protected RegistrarServiceContract $service;

    public function handle(RegistrarServiceFactory $registrarServiceFactory): void
    {
        try {
            $this->service = $registrarServiceFactory->make($this->credential);
            $this->sync();
        } catch (Throwable $e) {
            Log::error('Registrar sync job exception', [
                'job_class' => static::class,
                'job_id' => $this->job?->getJobId(),
                'job_attempts' => $this->job?->attempts(),
                'batch_id' => $this->batch()?->id,
                'credential_id' => $this->credential->id ?? null,
                'credential_service' => $this->credential->service ?? null,
                'exception_class' => $e::class,
                'exception_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
