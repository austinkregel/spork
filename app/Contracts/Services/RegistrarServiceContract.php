<?php

namespace App\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RegistrarServiceContract
{
    public function getDomains(int $limit = 10, int $page = 1): LengthAwarePaginator;
    public function getDomainNs(string $domain): array;
    public function updateDomainNs(string $domain, array $nameservers): array;
}
