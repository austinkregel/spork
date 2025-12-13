<?php

declare(strict_types=1);

namespace App\Services\Infrastructure\Provisioning;

use App\Jobs\CloudflareSyncAndPurgeJob;
use App\Models\Credential;
use App\Models\DnsZone;
use App\Models\Domain;
use App\Models\InfrastructureProvisionRequest;
use App\Services\Factories\DomainServiceFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProvisioningDomainService
{
    public function __construct(
        private readonly DomainServiceFactory $domainServiceFactory,
    ) {
    }

    public function handle(InfrastructureProvisionRequest $request, array $serverResult): array
    {
        $action = $request->payload['domain_action'] ?? 'none';

        if ($action === 'none') {
            return [];
        }

        if ($action === 'create') {
            return $this->handleCreate($request, $serverResult);
        }

        if ($action === 'link') {
            return $this->handleLink($request, $serverResult);
        }

        return [];
    }

    protected function handleCreate(InfrastructureProvisionRequest $request, array $serverResult): array
    {
        $dnsCredential = $request->dnsCredential;

        if (! $dnsCredential) {
            throw new \RuntimeException('DNS provider credential is required to create a domain.');
        }

        $domainName = $request->payload['new_domain']['name'];
        $domainService = $this->domainServiceFactory->make($dnsCredential);
        $domainService->createDomain($domainName);

        $domain = Domain::query()->firstOrCreate([
            'name' => $domainName,
        ], [
            'verification_key' => 'infra_'.Str::random(48),
            'credential_id' => $dnsCredential->id,
        ]);

        $domain->credential()->associate($dnsCredential);
        $domain->server_id = $serverResult['id'];
        $this->maybeAssignCloudflareZoneId($dnsCredential, $domainService, $domain);
        $this->attachDnsZone($domain, $dnsCredential);
        $domain->save();

        $this->publishRecords($dnsCredential, $domainService, $domain, $request, $serverResult);

        CloudflareSyncAndPurgeJob::dispatch($dnsCredential);

        return [
            'id' => $domain->id,
            'name' => $domain->name,
        ];
    }

    protected function handleLink(InfrastructureProvisionRequest $request, array $serverResult): array
    {
        $domain = Domain::withoutGlobalScope('active')
            ->whereKey($request->payload['existing_domain_id'])
            ->firstOrFail();

        /** @var Credential $dnsCredential */
        $dnsCredential = $request->dnsCredential ?? $domain->credential;
        $domainService = $this->domainServiceFactory->make($dnsCredential);

        $domain->server_id = $serverResult['id'];
        $this->maybeAssignCloudflareZoneId($dnsCredential, $domainService, $domain);
        $this->attachDnsZone($domain, $dnsCredential);
        $domain->save();

        $this->publishRecords($dnsCredential, $domainService, $domain, $request, $serverResult);

        CloudflareSyncAndPurgeJob::dispatch($dnsCredential);

        return [
            'id' => $domain->id,
            'name' => $domain->name,
        ];
    }

    protected function publishRecords(Credential $dnsCredential, $domainService, Domain $domain, InfrastructureProvisionRequest $request, array $serverResult): void
    {
        $records = $request->payload['records'] ?? [];
        $serverIp = Arr::get($serverResult, 'ip_address');
        $zoneIdentifier = $this->resolveZoneIdentifier($dnsCredential, $domain);

        if (empty($records) || ! $zoneIdentifier) {
            return;
        }

        $this->removeConflictingRecords($domainService, $zoneIdentifier, $records);

        foreach ($records as $record) {
            $value = ($record['use_server_ip'] ?? false) && $serverIp ? $serverIp : ($record['value'] ?? '');

            if (empty($value)) {
                continue;
            }

            $domainService->createDnsRecord($zoneIdentifier, [
                'type' => $record['type'],
                'name' => $record['name'],
                'content' => $value,
                'ttl' => $record['ttl'] ?? 'auto',
                'proxied' => $record['proxied'] ?? false,
            ]);
        }
    }

    protected function removeConflictingRecords($domainService, string $zoneIdentifier, array $records): void
    {
        if (! method_exists($domainService, 'getDns') || ! method_exists($domainService, 'deleteDnsRecord')) {
            return;
        }

        $existing = $domainService->getDns($zoneIdentifier, null, 1000, 1);
        $recordsByKey = Collection::make($records)->mapToGroups(function ($record) {
            return [$record['type'].'|'.strtolower($record['name']) => $record];
        });

        foreach ($existing->items() as $existingRecord) {
            $key = ($existingRecord['type'] ?? '').'|'.strtolower($existingRecord['name'] ?? '');

            if (! $recordsByKey->has($key)) {
                continue;
            }

            if (! empty($existingRecord['id'])) {
                $domainService->deleteDnsRecord($zoneIdentifier, (string) $existingRecord['id']);
            }
        }
    }

    protected function resolveZoneIdentifier(Credential $credential, Domain $domain): ?string
    {
        if ($credential->service === Credential::CLOUDFLARE) {
            return $domain->cloudflare_id ?? $domain->domain_id;
        }

        return $domain->name;
    }

    protected function attachDnsZone(Domain $domain, Credential $credential): void
    {
        if ($domain->dns_zone_id) {
            return;
        }

        $zone = DnsZone::query()->firstOrCreate([
            'credential_id' => $credential->id,
            'name' => $domain->name,
        ], [
            'user_id' => $credential->user_id,
            'provider' => $credential->service,
            'account' => $credential->settings['account_id'] ?? null,
            'external_id' => $domain->cloudflare_id ?? null,
        ]);

        $domain->dns_zone_id = $zone->id;
    }

    protected function maybeAssignCloudflareZoneId(Credential $credential, $domainService, Domain $domain): void
    {
        if ($credential->service !== Credential::CLOUDFLARE || $domain->cloudflare_id) {
            return;
        }

        $zoneId = $this->locateCloudflareZoneId($domainService, $domain->name);
        if ($zoneId) {
            $domain->cloudflare_id = $zoneId;
            $domain->domain_id = $zoneId;
        }
    }

    protected function locateCloudflareZoneId($domainService, string $domainName): ?string
    {
        if (! method_exists($domainService, 'getDomains')) {
            return null;
        }

        $paginator = $domainService->getDomains(1000, 1);
        $items = method_exists($paginator, 'items') ? $paginator->items() : (array) $paginator;

        foreach ($items as $zone) {
            $name = $zone['domain'] ?? $zone['name'] ?? null;
            if ($name === $domainName) {
                return $zone['id'] ?? $zone['domain_id'] ?? null;
            }
        }

        return null;
    }
}

