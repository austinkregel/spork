<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\CredentialRepositoryContract;
use App\Models\Credential;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CredentialRepository implements CredentialRepositoryContract
{
    public function findAllOfType(string $type, ?int $limit = 15, ?int $page = 1): LengthAwarePaginator
    {
        return Credential::query()
            ->where('type', $type)
            ->paginate($limit, ['*'], 'page', $page);
    }
}
