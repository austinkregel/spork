<?php

declare(strict_types=1);

namespace App\Contracts\Services;

interface CloudflareDomainServiceContract extends DomainServiceContract
{
    public function hasEmailRouting(string $domain): bool;
}
