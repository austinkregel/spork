<?php

declare(strict_types=1);

namespace App\Services\Factories;

use App\Contracts\Services\DomainServiceContract;
use App\Models\Credential;
use App\Services\Domain\CloudflareDomainService;
use App\Services\Server\DigitalOceanService;

class DomainServiceFactory
{
    public function make(Credential $credential): DomainServiceContract
    {
        return match ($credential->service) {
            Credential::CLOUDFLARE => new CloudflareDomainService($credential),
            Credential::DIGITAL_OCEAN => new DigitalOceanService($credential)
        };
    }
}
