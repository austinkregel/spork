<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RegistrarServiceContract
{
    public function getTlds(): array;

    public function getDomains(int $limit = 10, int $page = 1): LengthAwarePaginator;

    public function getDomainNs(string $domain): array;

    public function updateDomainNs(string $domain, array $nameservers): array;

    public function searchDomain(string $domain): array;

    public function registerDomain(string $domain, int $years = 1): array;

    public function renewDomain(string $domain, int $years = 1): array;
}
