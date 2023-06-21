<?php

namespace App\Services\Registrar;

use App\Contracts\Services\CloudflareRegistrarServiceContract;
use App\Models\Credential;
use App\Services\Domain\CloudflareDomainService;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;

class CloudflareRegistrarService implements CloudflareRegistrarServiceContract
{
    public const CLOUDFLARE_URL = 'https://api.cloudflare.com/client/v4/';

    protected string $email;
    protected string $apiKey;
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
        $domains = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Auth-Email' => $this->email,
            'X-Auth-Key' => $this->apiKey,
        ])->get(static::CLOUDFLARE_URL.'accounts/'. $this->accountId .'/registrar/domains', []);

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
        // TODO: Implement getDomainNs() method.
    }

    public function updateDomainNs(string $domain, array $nameservers): array
    {
        // TODO: Implement updateDomainNs() method.
    }
}
