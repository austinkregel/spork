<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Credential;
use App\Models\DnsZone;
use App\Models\Domain;
use App\Services\Factories\DomainServiceFactory;
use App\Services\Factories\RegistrarServiceFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class CloudflareSyncAndPurgeJob extends AbstractSyncDomainResource
{
    public function handle(DomainServiceFactory $serviceFactory): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }
        $credentials = [$this->credential];
        foreach ($credentials as $credential) {
            try {
                $this->service = $serviceFactory->make($credential);
                $this->credential = $credential;
                $this->sync();
            } catch (UnauthorizedHttpException $e) {
                // There are some times when we have a different credential for the same uuid.
                // this would be when things are duplicated, the uuid stays the same, but the credentials themselves and what they access
                // change. So we need to try all our credentials of a uuid to ensure that we're syncing everything available.
            }
        }
    }

    public function sync(): void
    {
        $page = 1;
        $registrarService = (new RegistrarServiceFactory)->make($this->credential);
        $dispatcher = Model::getEventDispatcher();
        Model::unsetEventDispatcher();
        do {
            $domains = $this->service->getDomains(100, $page++);
            foreach ($domains as $domain) {
                // In order for domain jobs to be able to run, we need the domain to exist from a registrar.
                $localDomain = Domain::where('name', $domain['domain'])->first();

                if (empty($localDomain)) {
                    info('No local domain for the provided credentials', [
                        'domain' => $domain['domain'],
                        'credential' => $localDomain,
                    ]);

                    // If we don't have the domain in question synced via registrars we don't want to touch it.
                    continue;
                }

                $dnsZone = $this->syncDnsZone($domain);

                if (empty($localDomain->cloudflare_id)) {
                    $localDomain->cloudflare_id = $domain['id'];
                }

                if ($localDomain->dns_zone_id !== $dnsZone?->id) {
                    $localDomain->dns_zone_id = $dnsZone?->id;
                }

                if ($localDomain->isDirty()) {
                    $localDomain->save();
                }

                //                $registrarService->updateDomainNs($localDomain->name, $domain['name_servers']);
                if (empty($localDomain->cloudflare_id)) {
                    continue;
                }
                $dnsResults = $this->service->getDns($localDomain->cloudflare_id);
                foreach ($dnsResults as $dnsRecord) {
                    $localDomain->records()->updateOrCreate([
                        'type' => $dnsRecord['type'],
                        'name' => $dnsRecord['name'],
                    ], [
                        'record_id' => $dnsRecord['id'],
                        'ttl' => $dnsRecord['ttl'],
                        'value' => $dnsRecord['content'],
                        'priority' => $dnsRecord['priority'],
                        'proxied_through_cloudflare' => $dnsRecord['proxied_through_cloudflare'],
                        'dns_zone_id' => $dnsZone?->id,
                    ]);

                    if (! $localDomain->wasRecentlyCreated) {
                        $localDomain->update([
                            'ttl' => $dnsRecord['ttl'],
                            'value' => $dnsRecord['content'],
                            'priority' => $dnsRecord['priority'],
                            'proxied_through_cloudflare' => $dnsRecord['proxied_through_cloudflare'],
                        ]);
                    }
                }

                $localDomain->records()
                    // Anything we didn't recently update is probably gone.
                    ->where('updated_at', '<=', now()->subHours(5))
                    ->delete();
            }
        } while ($domains->hasMorePages());
        Model::setEventDispatcher($dispatcher);
    }

    protected function syncDnsZone(array $domain): ?DnsZone
    {
        $zone = DnsZone::query()->firstOrNew([
            'external_id' => $domain['id'],
            'credential_id' => $this->credential->id,
        ]);

        $zone->user_id = $zone->user_id ?? $this->credential->user_id;
        $zone->name = $domain['domain'];
        $zone->provider = Credential::CLOUDFLARE;
        $zone->account = $this->credential->settings['account_id'] ?? null;
        $zone->settings = array_merge($zone->settings ?? [], [
            'name_servers' => $domain['name_servers'] ?? [],
            'original_name_servers' => $domain['original_name_servers'] ?? [],
            'price' => $domain['price'] ?? null,
        ]);

        if ($zone->isDirty()) {
            $zone->save();
        } elseif (! $zone->exists) {
            $zone->save();
        }

        return $zone;
    }
}
