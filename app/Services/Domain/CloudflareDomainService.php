<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Contracts\Services\CloudflareDomainServiceContract;
use App\Contracts\Services\DomainServiceContract;
use App\Models\Credential;
use App\Models\Domain;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class CloudflareDomainService implements CloudflareDomainServiceContract
{
    public const CLOUDFLARE_URL = 'https://api.cloudflare.com/client/v4/';

    protected string $apiKey;

    protected string $email;

    protected string $accountId;

    public function __construct(
        public Credential $credential
    ) {
        $this->apiKey = $credential->access_token;
        $this->email = $credential->settings['email'];
        $this->accountId = $credential->settings['account_id'];
    }

    /*
     * Since this is the Registrar service, we should only get domains that actually are handled by the CF registrar.
     */
    public function getDomains(int $limit = 10, int $page = 1): LengthAwarePaginator
    {
        $response = Http::withHeaders([
            'X-Auth-Key' => $this->apiKey,
            'Content-Type' => 'application/json',
            'X-Auth-Email' => $this->email,
        ])->get(static::CLOUDFLARE_URL.'/zones', [
            'per_page' => $limit,
            'page' => $page,
            //            'status' => 'active',
            'match' => 'all',
        ]);

        throw_if(empty($response->json('result')), UnauthorizedHttpException::class, 'This credential does not have access');

        return new LengthAwarePaginator(
            array_map(fn ($zone) => [
                'id' => $zone['id'],
                'domain' => $zone['name'],
                'created_at' => Carbon::parse($zone['activated_on']),
                'price' => $zone['plan']['price'] ?? 0,
                'name_servers' => $zone['name_servers'] ?? [],
                'original_name_servers' => $zone['original_name_servers'] ?? [],
            ], $response->json('result')),
            $response->json('result_info.total_count'),
            $response->json('result_info.per_page')
        );
    }

    public function deleteDnsRecord(string $domain, string $dnsRecordId): void
    {
        Http::withHeaders([
            'X-Auth-Key' => $this->apiKey,
            'x-auth-email' => $this->email,
        ])->delete(static::CLOUDFLARE_URL."/zones/$domain/dns_records/$dnsRecordId");
    }

    public function getDomainNs(string $domain): array
    {
        // TODO: Implement getDomainNs() method.
    }

    public function updateDomainNs(string $domain, array $nameservers): array
    {
        // TODO: Implement updateDomainNs() method.
    }

    /**
     * @return array Nameservers
     */
    public function createDomain(string $domain): array
    {
        $response = Http::withHeaders([
            'X-Auth-Key' => $this->apiKey,
            'x-auth-email' => $this->email,
        ])->post(static::CLOUDFLARE_URL.'/zones', [
            'account' => [
                'id' => $this->accountId,
            ],
            'name' => $domain,
            'jump_start' => true,
        ]);

        if (empty($response->json('result.name_servers'))) {
            if (str_contains($response->json('errors.0.message'), 'already exists')) {
                $domains = array_filter($allDomains = $this->getDomains(1000, 1)->items(), fn ($d) => $d['domain'] === $domain);

                foreach ($domains as $domain) {
                    return $domain['name_servers'];
                }

                throw new \Exception('Domain exists but could not be found');
            }

            throw $response->toException();
        }

        return $response->json('result.name_servers');
    }

    public function getDns(string $domain, ?string $type = null, int $limit = 10, int $page = 1): LengthAwarePaginator
    {
        $response = Http::withHeaders([
            'X-Auth-Key' => $this->apiKey,
            'x-auth-email' => $this->email,
        ])->get(static::CLOUDFLARE_URL."/zones/$domain/dns_records", array_merge([
            'per_page' => $limit,
            'page' => $page,
        ], isset($type) ? compact('type') : []));

        $data = $response->json('result');

        if (! isset($data)) {
            dd($response->json());
        }

        return new LengthAwarePaginator(
            array_map(fn ($dnsRecord) => [
                'id' => $dnsRecord['id'],
                'name' => $dnsRecord['name'] ?? null,
                'type' => $dnsRecord['type'] ?? null,
                'content' => $dnsRecord['content'] ?? null,
                'ttl' => $dnsRecord['ttl'] ?? 10,
                'priority' => $dnsRecord['priority'] ?? null,
                'proxied_through_cloudflare' => $dnsRecord['proxied'] ?? false,
            ], $data),
            $response->json('result_info.total_count'),
            $response->json('result_info.per_page'),
        );
    }

    public function createDnsRecord(string $domain, array $dnsRecordArray): void
    {
        $response = Http::withHeaders([
            'X-Auth-Key' => $this->apiKey,
            'x-auth-email' => $this->email,
            'content-type' => 'application/json',
        ])->post(static::CLOUDFLARE_URL."/zones/$domain/dns_records", $dnsRecordArray);

        $id = $response->json('result.id');

        if (empty($id)) {
            throw new \Exception('Could not create DNS record');
        }
    }

    public function hasEmailRouting(string $domain): bool
    {
        $response = Http::withHeaders([
            'X-Auth-Key' => $this->apiKey,
            'x-auth-email' => $this->email,
            'content-type' => 'application/json',
        ])->get(static::CLOUDFLARE_URL."/zones/$domain/email/routing");

        return $response->json()['result']['enabled'];
    }

    public function createRestrictiveDnsDkimDmarcRecords(Domain $domain, DomainServiceContract $service)
    {
        $service->createDnsRecord($domain->name, [
            'type' => 'TXT',
            'name' => $domain->name,
            'content' => 'v=spf1 -all',
            'ttl' => 'auto',
        ]);
        $service->createDnsRecord($domain->name, [
            'type' => 'TXT',
            'name' => '*._domainkey',
            'content' => 'v=DKIM1; p=',
            'ttl' => 'auto',
        ]);
        $service->createDnsRecord($domain->name, [
            'type' => 'TXT',
            'name' => '_dmarc',
            'content' => 'v=DMARC1; p=reject; sp=reject; adkim=s; aspf=s;',
            'ttl' => 'auto',
        ]);
    }

    public function createEmailRouting(Domain $domain)
    {
        $this->createDnsRecord($domain->name, [
            'type' => 'mx',
            'name' => '@',
            'content' => 'route1.mx.cloudflare.net',
            'priority' => 79,
            'ttl' => 'auto',
        ]);
        $this->createDnsRecord($domain->name, [
            'type' => 'mx',
            'name' => '@',
            'content' => 'route2.mx.cloudflare.net',
            'priority' => 39,
            'ttl' => 'auto',
        ]);
        $this->createDnsRecord($domain->name, [
            'type' => 'mx',
            'name' => '@',
            'content' => 'route3.mx.cloudflare.net',
            'priority' => 11,
            'ttl' => 'auto',
        ]);
        $this->createDnsRecord($domain->name, [
            'type' => 'TXT',
            'name' => '@',
            'content' => 'v=spf1 include:_spf.mx.cloudflare.net ~all',
            'ttl' => 'auto',
        ]);
    }

    public function getAnalytics(Domain $domain, Carbon $startDate, Carbon $endDate)
    {
        $zone = $domain->cloudflare_id;
        $response = Http::withHeaders([
            'X-Auth-Key' => $this->apiKey,
            'x-auth-email' => $this->email,
            'content-type' => 'application/json',
        ])->get(static::CLOUDFLARE_URL."/zones/$zone/dns_analytics/report/bytime?".http_build_query([
            'dimensions' => implode(',', $dimensions = [
                'queryName',
                'responseCode',
                'origin',
            ]),
            'metrics' => implode(',', $metrics = [
                'queryCount',
                'uncachedCount',
                'staleCount',
            ]),
            'since' => $startDate->startOfDay(),
            'until' => $endDate->endOfDay(),
        ]));

        $results = $response->json('result.data');
        $intervals = $response->json('result.time_intervals');

        $actualData = [];
        foreach ($results as $collectionOfData) {
            $dimension = array_combine($dimensions, $collectionOfData['dimensions']);
            $metric = array_combine($metrics, $collectionOfData['metrics']);
            foreach ($intervals as $dataIndex => $intervalSet) {
                if ($metric['queryCount'][$dataIndex] !== 0 || $metric['uncachedCount'][$dataIndex] !== 0 || $metric['staleCount'][$dataIndex] !== 0) {
                    $actualData[] = [
                        'dimensions' => [
                            'query_name' => $dimension['queryName'],
                            'response_code' => $dimension['responseCode'],
                            'origin' => $dimension['origin'],
                            'date' => Carbon::parse($intervalSet[0]),
                            'domain_id' => $domain->id,
                        ],
                        'metrics' => [
                            'query_count' => $metric['queryCount'][$dataIndex],
                            'uncached_count' => $metric['uncachedCount'][$dataIndex],
                            'stale_count' => $metric['staleCount'][$dataIndex],
                        ],
                    ];
                }
            }
        }

        foreach ($actualData as $rowOfData) {
            // Won't Guarantee unique data...
            [
                'metrics' => $metrics,
                'dimensions' => $dimensions,
            ] = $rowOfData;

            $analytic = $domain->domainAnalytics()->firstOrCreate($dimensions, $metrics);

            if (! $analytic->wasRecentlyCreated) {
                $analytic->update($metrics);
            }
        }
    }
}
