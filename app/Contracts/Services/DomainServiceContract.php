<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DomainServiceContract
{
    public function createDomain(string $domain): array;

    public function getDomains(int $limit = 10, int $page = 1): LengthAwarePaginator;

    public function getDns(string $domain, string $type = 'A', int $limit = 10, int $page = 1): LengthAwarePaginator;

    public function deleteDnsRecord(string $domain, string $dnsRecordId): void;

    public function createDnsRecord(string $domain, array $dnsRecordArray): void;
}
