<?php

declare(strict_types=1);

namespace App\Services\Registrar;

use App\Contracts\Services\CloudflareRegistrarServiceContract;
use App\Models\Credential;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;

class CloudflareRegistrarService implements CloudflareRegistrarServiceContract
{
    public const CLOUDFLARE_URL = 'https://api.cloudflare.com/client/v4/';

    protected string $email;

    protected string $apiKey;

    protected string $accountId;

    protected string $accessToken;

    public function __construct(
        public Credential $credential
    ) {
        $this->apiKey = $credential->api_key;
        $this->accessToken = $this->credential->access_token;
        $this->email = $credential->settings['email'];
        $this->accountId = $credential->settings['account_id'];
    }

    /*
     * Since this is the Registrar service, we should only get domains that actually are handled by the CF registrar.
     */
    public function getDomains(int $limit = 10, int $page = 1): LengthAwarePaginator
    {
        $domains = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$this->accessToken,
            'X-Auth-Email' => $this->email,
            'X-Auth-Key' => $this->apiKey,
        ])->get(static::CLOUDFLARE_URL.'accounts/'.$this->accountId.'/registrar/domains', []);

        dd($domains->json());
        return new LengthAwarePaginator(
            array_map(fn ($zone) => [
                'id' => $zone['registry_object_id'],
                'domain' => $zone['name'],
                'expires_at' => $expiresAt = Carbon::parse($zone['expires_at']),
                'created_at' => $expiresAt->copy()->subYear(),
                'is_expired' => $expiresAt->isAfter(now()),
                'is_locked' => $zone['locked'],
                'is_auto_renewing' => $zone['auto_renew'],
                'has_whois_guard' => $zone['privacy'],
            ], $domains->json('result')),
            $domains->json('result_info.total_count'),
            $domains->json('result_info.per_page')
        );
    }

    public function getDomainNs(string $domain): array
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Auth-Email' => $this->email,
            'Authorization' => 'Bearer '.$this->accessToken,
            'X-Auth-Key' => $this->apiKey,
        ])->get(static::CLOUDFLARE_URL.'accounts/'.$this->accountId.'/registrar/domains/'.$domain);

        if (! $response->successful()) {
            throw new \RuntimeException('Unable to fetch Cloudflare registrar domain nameservers');
        }

        $result = $response->json('result');

        if (! is_array($result) || ! isset($result['current_nameservers'])) {
            throw new \RuntimeException('Cloudflare registrar response missing current_nameservers');
        }

        return (array) $result['current_nameservers'];
    }

    public function updateDomainNs(string $domain, array $nameservers): array
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$this->accessToken,
            'X-Auth-Email' => $this->email,
            'X-Auth-Key' => $this->apiKey,
        ])->put(static::CLOUDFLARE_URL.'accounts/'.$this->accountId.'/registrar/domains/'.$domain.'/nameservers', [
            'nameservers' => array_values($nameservers),
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Unable to update Cloudflare registrar domain nameservers');
        }

        return array_values($nameservers);
    }

    public function getTlds(): array
    {
        throw new \BadMethodCallException('Cloudflare registrar TLD listing is not implemented.');
    }

    public function searchDomain(string $domain): array
    {
        throw new \BadMethodCallException('Cloudflare registrar domain search is not implemented.');
    }

    public function registerDomain(string $domain, int $years = 1): array
    {
        throw new \BadMethodCallException('Cloudflare registrar domain registration is not implemented.');
    }

    public function renewDomain(string $domain, int $years = 1): array
    {
        throw new \BadMethodCallException('Cloudflare registrar domain renewal is not implemented.');
    }
}
