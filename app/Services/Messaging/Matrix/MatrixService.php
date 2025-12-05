<?php

declare(strict_types=1);

namespace App\Services\Messaging\Matrix;

use App\Contracts\Repositories\CredentialRepositoryContract;
use App\Contracts\Services\Messaging\MatrixServiceContract;
use App\Models\Credential;
use Illuminate\Support\Facades\Http;

class MatrixService implements MatrixServiceContract
{
    public function __construct(
        protected CredentialRepositoryContract $credential_repository,
    ) {}

    public function fetchEvent(string $event_id): ?array
    {
        $credential = $this->resolveMatrixCredential();

        if (! $credential) {
            return null;
        }

        $matrix_server = rtrim((string) ($credential->settings['matrix_server'] ?? ''), '/');
        $access_token = (string) ($credential->access_token ?? '');

        if ($matrix_server === '' || $access_token === '') {
            return null;
        }

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$access_token,
        ])->get(sprintf(
            '%s/_matrix/client/v3/events/%s',
            $matrix_server,
            rawurlencode($event_id)
        ));

        if ($response->status() === 404) {
            return null;
        }

        $response->throw();

        $payload = $response->json();

        return is_array($payload) ? $payload : null;
    }

    protected function resolveMatrixCredential(): ?Credential
    {
        $paginator = $this->credential_repository->findAllOfType(Credential::TYPE_MATRIX);

        $credential = collect($paginator->items())->first();

        return $credential instanceof Credential ? $credential : null;
    }
}


