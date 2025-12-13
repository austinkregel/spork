<?php

declare(strict_types=1);

namespace App\Services\Factories;

use App\Contracts\Services\DigitalOceanServiceContract;
use App\Contracts\Services\ServerServiceContract;
use App\Models\Credential;
use RuntimeException;

class ServerServiceFactory
{
    public function make(Credential $credential): ServerServiceContract
    {
        return match ($credential->service) {
            Credential::DIGITAL_OCEAN => app(DigitalOceanServiceContract::class, [
                'credential' => $credential,
            ]),
            default => throw new RuntimeException(sprintf(
                'Unsupported server provider [%s]',
                $credential->service
            )),
        };
    }
}
