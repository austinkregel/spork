<?php

declare(strict_types=1);

namespace App\Services\Registrar;

use App\Contracts\Services\CloudflareRegistrarServiceContract;
use App\Models\Credential;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

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
        $this->email = (string) data_get($credential->settings, 'email', '');
        $this->accountId = (string) data_get($credential->settings, 'account_id', '');

        if ($this->email === '') {
            throw new InvalidArgumentException('Cloudflare registrar credential is missing settings.email');
        }

        if ($this->accountId === '') {
            throw new InvalidArgumentException('Cloudflare registrar credential is missing settings.account_id');
        }

        if ($this->accessToken === '') {
            throw new InvalidArgumentException('Cloudflare registrar credential is missing access_token');
        }
    }

    /*
     * Since this is the Registrar service, we should only get domains that actually are handled by the CF registrar.
     */
    public function getDomains(int $limit = 10, int $page = 1): LengthAwarePaginator
    {
        $response = Http::timeout(30)->retry(2, 250)->withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$this->accessToken,
            'X-Auth-Email' => $this->email,
            'X-Auth-Key' => $this->apiKey,
        ])->get(static::CLOUDFLARE_URL.'accounts/'.$this->accountId.'/registrar/domains', [
            'per_page' => $limit,
            'page' => $page,
        ]);

        if (! $response->successful()) {
            throw $response->toException() ?? new RuntimeException('Cloudflare registrar request failed.');
        }

        $result = $response->json('result');
        if (! is_array($result)) {
            throw new RuntimeException('Cloudflare registrar response missing result array.');
        }

        return new LengthAwarePaginator(
            array_map(fn ($zone) => [
                'id' => $zone['registry_object_id'],
                'domain' => $zone['name'],
                'expires_at' => $expiresAt = Carbon::parse($zone['expires_at']),
                'created_at' => $expiresAt->copy()->subYear(),
                'is_expired' => $expiresAt->isBefore(now()),
                'is_locked' => $zone['locked'],
                'is_auto_renewing' => $zone['auto_renew'],
                'has_whois_guard' => $zone['privacy'],
            ], $result),
            (int) ($response->json('result_info.total_count') ?? count($result)),
            (int) ($response->json('result_info.per_page') ?? $limit)
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
