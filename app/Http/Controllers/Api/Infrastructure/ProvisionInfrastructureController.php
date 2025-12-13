<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Infrastructure;

use App\Contracts\Services\ServerServiceContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Infrastructure\ProvisionInfrastructureRequest;
use App\Http\Resources\Infrastructure\ProvisionRequestResource;
use App\Jobs\Infrastructure\ProvisionInfrastructureJob;
use App\Models\Credential;
use App\Models\Domain;
use App\Models\InfrastructureProvisionRequest;
use App\Models\User;
use App\Services\Factories\ServerServiceFactory;
use Illuminate\Http\Request;
use RuntimeException;

class ProvisionInfrastructureController extends Controller
{
    public function __construct(
        private readonly ServerServiceFactory $serverServiceFactory,
    ) {
    }

    public function store(ProvisionInfrastructureRequest $request): ProvisionRequestResource
    {
        $user = $request->user();
        $payload = $request->validated();

        $providerCredential = $user->credentials()
            ->whereKey($payload['provider_credential_id'])
            ->firstOrFail();

        if ($providerCredential->type !== Credential::TYPE_SERVER) {
            abort(422, 'Selected credential is not a compute provider.');
        }

        $dnsCredential = null;

        if ($payload['dns_provider_credential_id'] ?? null) {
            $dnsCredential = $user->credentials()
                ->whereKey($payload['dns_provider_credential_id'])
                ->firstOrFail();
        }

        $payload['server']['ssh_key_ids'] = $this->resolveProviderSshKeyIds(
            $user,
            $providerCredential,
            $payload['server']['ssh_key_ids'] ?? [],
        );

        if (($payload['domain_action'] ?? null) === 'create' && ! $dnsCredential) {
            abort(422, 'DNS provider credential is required to create a domain.');
        }

        if (($payload['domain_action'] ?? null) === 'link') {
            $domain = Domain::withoutGlobalScope('active')
                ->whereKey($payload['existing_domain_id'])
                ->firstOrFail();

            abort_unless(
                $domain->credential?->user_id === $user->id,
                403,
                'You do not own the selected domain.'
            );
        }

        $provisionRequest = InfrastructureProvisionRequest::query()->create([
            'user_id' => $user->id,
            'provider_credential_id' => $providerCredential->id,
            'dns_credential_id' => $dnsCredential?->id,
            'status' => 'pending',
            'payload' => $payload,
        ]);

        ProvisionInfrastructureJob::dispatch($provisionRequest->id);

        return new ProvisionRequestResource($provisionRequest);
    }

    public function show(Request $request, InfrastructureProvisionRequest $provisionRequest): ProvisionRequestResource
    {
        abort_unless($provisionRequest->user_id === $request->user()->id, 403);

        return new ProvisionRequestResource($provisionRequest->fresh());
    }

    /**
     * @param array<int|string> $sshKeyIds
     * @return array<int|string>
     */
    protected function resolveProviderSshKeyIds(User $user, Credential $providerCredential, array $sshKeyIds): array
    {
        if ($sshKeyIds === []) {
            return [];
        }

        $sshCredentials = $user->credentials()
            ->where('type', Credential::TYPE_SSH)
            ->whereIn('id', $sshKeyIds)
            ->get()
            ->keyBy('id');

        return array_map(function ($id) use ($sshCredentials, $providerCredential) {
            $credential = $sshCredentials->get((int) $id);

            if (! $credential) {
                return (string) $id;
            }

            $resolved = $this->resolveProviderSshKeyId($credential, $providerCredential);

            return $resolved ?? (string) $id;
        }, $sshKeyIds);
    }

    protected function resolveProviderSshKeyId(Credential $sshCredential, Credential $providerCredential): ?string
    {
        $serviceKey = sprintf('%s_key_id', str_replace('-', '_', $providerCredential->service));

        $candidates = [
            $sshCredential->settings[$serviceKey] ?? null,
            $sshCredential->settings['key_id'] ?? null,
            $sshCredential->settings['droplet_key_id'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (! empty($candidate)) {
                return (string) $candidate;
            }
        }

        return $this->provisionProviderSshKey($sshCredential, $providerCredential);
    }

    protected function provisionProviderSshKey(Credential $sshCredential, Credential $providerCredential): ?string
    {
        $service = $this->makeServerService($providerCredential);

        if (! $service) {
            return null;
        }

        $publicKey = $sshCredential->settings['pub_key'] ?? null;

        if (empty($publicKey)) {
            return null;
        }

        $fingerprint = $sshCredential->settings['fingerprint'] ?? null;

        $key = $service->findSshKeyByFingerprint($fingerprint);

        if (! $key) {
            $key = $service->createSshKey($sshCredential->name, $publicKey);
        }

        if (! $key) {
            return null;
        }

        $serviceKey = sprintf('%s_key_id', str_replace('-', '_', $providerCredential->service));
        $sshCredential->settings = array_merge($sshCredential->settings ?? [], [
            $serviceKey => $key['id'] ?? null,
            'fingerprint' => $fingerprint ?? ($key['fingerprint'] ?? null),
        ]);
        $sshCredential->save();

        return isset($key['id']) ? (string) $key['id'] : null;
    }

    protected function makeServerService(Credential $providerCredential): ?ServerServiceContract
    {
        try {
            return $this->serverServiceFactory->make($providerCredential);
        } catch (RuntimeException) {
            return null;
        }
    }
}

