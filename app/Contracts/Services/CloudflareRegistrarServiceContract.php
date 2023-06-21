<?php

declare(strict_types=1);

namespace App\Contracts\Services;

interface CloudflareRegistrarServiceContract extends RegistrarServiceContract
{
    // The primary features provided by cloudflare will be managed in the domain service, not the registrar service.
}
