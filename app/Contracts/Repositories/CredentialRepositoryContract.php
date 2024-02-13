<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CredentialRepositoryContract
{
    public function findAllOfType(string $type): LengthAwarePaginator;
}
