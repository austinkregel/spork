<?php

declare(strict_types=1);

namespace App\Services\Registrar;

use App\Contracts\Services\NamecheapServiceContract;
use App\Models\Credential;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;

abstract class DomainName
{
    public array $attributes = [];

    abstract public function attributesToVendor(): mixed;

    abstract public function setVendorModel(mixed $vendorModel): array;

    abstract public function setAttributes(array $attributes): DomainName;
}
class TucowsEnomService implements NamecheapServiceContract
{
    public const ENOM_URL = 'https://reseller.enom.com/interface.asp';

    public function __construct(
        public Credential $credential
    ) {}

    public function getDomains(int $limit = 10, int $page = 1): LengthAwarePaginator
    {
        $url = static::ENOM_URL.'?'.http_build_query([
            'ApiUser' => $this->credential->settings['api_user'],
            'ApiKey' => $this->credential->access_token,
            'UserName' => $this->credential->settings['username'],
            'Command' => 'namecheap.domains.getList',
            'ClientIp' => $this->credential->settings['client_ip'],
            'PageSize' => $limit,
            'Page' => $page,
        ]);

        $response = cache()->remember($url, now()->addHour(), fn () => Http::get($url)->body());

        $domainResponse = json_decode(json_encode(simplexml_load_string($response)));

        if (isset($domainResponse->Errors->Error)) {
            throw new \Exception($domainResponse->Errors->Error);
        }

        $domains = array_map(fn ($obj) => $obj->{'@attributes'}, $domainResponse->CommandResponse->DomainGetListResult->Domain ?? []);

        // We want to convert this to DomainName
        return new LengthAwarePaginator(
            array_map(fn ($domain) => [
                'id' => (int) $domain->ID,
                'domain' => $domain->Name,
                'is_expired' => $domain->IsExpired === 'true',
                'is_locked' => $domain->IsLocked === 'true',
                'is_auto_renewing' => $domain->AutoRenew === 'true',
                'has_whois_guard' => $domain->WhoisGuard === 'ENABLED',
                // 'original' => (array) $domain,
                'created_at' => Carbon::parse($domain->Created),
                'expires_at' => Carbon::parse($domain->Expires),
                //                'renews_at' => $this->fetchPriceOfRenewal($domain->Name),
            ], $domains),
            $domainResponse->CommandResponse->Paging->TotalItems ?? 0,
            $limit,
            $page
        );
    }

    public function getDomainNs(string $domain): array
    {
        [$domainPart, $tld] = explode('.', $domain, 2);
        $url = static::ENOM_URL.'?'.http_build_query([
            'ApiUser' => $this->credential->settings['api_user'],
            'ApiKey' => $this->credential->access_token,
            'UserName' => $this->credential->settings['username'],
            'Command' => 'namecheap.domains.dns.getList',
            'ClientIp' => $this->credential->settings['client_ip'],
            'SLD' => $domainPart,
            'TLD' => $tld,
        ]);
        $xmlDebugResponse = cache()->remember($url, now()->addHour(), fn () => Http::get($url)->body());

        $domainResponse = json_decode(json_encode(simplexml_load_string($xmlDebugResponse)));

        if (isset($domainResponse->Errors->Error)) {
            throw new \Exception($domainResponse->Errors->Error);
        }

        return (array) ($domainResponse->CommandResponse->DomainDNSGetListResult->Nameserver ?? []);
    }

    public function getTlds(): array
    {
        $url = static::ENOM_URL.'?'.http_build_query([
            'ApiUser' => $this->credential->settings['api_user'],
            'ApiKey' => $this->credential->access_token,
            'UserName' => $this->credential->settings['username'],
            'Command' => 'namecheap.domains.getTldList',
            'ClientIp' => $this->credential->settings['client_ip'],
        ]);
        $xmlDebugResponse = cache()->remember($url, now()->addHour(), fn () => Http::get($url)->body());

        $domainResponse = json_decode(json_encode(simplexml_load_string($xmlDebugResponse)));

        if (isset($domainResponse->Errors->Error)) {
            throw new \Exception($domainResponse->Errors->Error);
        }

        $parser = xml_parser_create();
        xml_parse_into_struct($parser, $xmlDebugResponse, $data);
        xml_parser_free($parser);

        $tlds = array_values(array_filter(
            $data,
            fn ($row) => ($row['tag'] ?? null) === 'TLD'
                && in_array($row['type'] ?? null, ['open', 'complete'], true)
                && (($row['attributes']['ISAPIREGISTERABLE'] ?? 'false') === 'true')
        ));

        return array_map(
            fn ($tld) => [
                'name' => strtolower($tld['attributes']['NAME'] ?? ''),
                'registerable' => ($tld['attributes']['ISAPIREGISTERABLE'] ?? 'false') === 'true',
            ],
            $tlds
        );
    }

    public function updateDomainNs(string $domain, array $nameservers): array
    {
        [$domainPart, $tld] = explode('.', $domain, 2);

        $response = Http::get(static::ENOM_URL.'?'.http_build_query([
            'ApiUser' => $this->credential->settings['api_user'],
            'ApiKey' => $this->credential->access_token,
            'UserName' => $this->credential->settings['username'],
            'Command' => 'namecheap.domains.dns.setCustom',
            'ClientIp' => $this->credential->settings['client_ip'],
            'SLD' => $domainPart,
            'TLD' => $tld,
            'Nameservers' => implode(',', $nameservers),
        ]));

        $domainResponse = json_decode(json_encode(simplexml_load_string($xmlDebugResponse = $response->body())));

        if (isset($domainResponse->Errors->Error)) {
            throw new \Exception($domainResponse->Errors->Error);
        }

        return $nameservers;
    }

    public function fetchPriceOfRenewal(string $domain): string
    {
        [$domainPart, $tld] = explode('.', $domain, 2);

        return cache()->remember($key = 'tld-pricing-for-enom.'.$tld, now()->addHour(), function () use ($tld) {
            $response = Http::get(static::ENOM_URL.'?'.http_build_query([
                // Auth
                'ApiUser' => $this->credential->settings['api_user'],
                'ApiKey' => $this->credential->access_token,
                'UserName' => $this->credential->settings['username'],
                'ClientIp' => $this->credential->settings['client_ip'],
                // command
                'Command' => 'namecheap.users.getPricing',
                // request deets
                'ProductType' => 'DOMAIN',
                'ActionName' => 'RENEW',
                'ProductName' => $tld,
            ]));

            $domainResponse = json_decode(json_encode(simplexml_load_string($xmlDebugResponse = $response->body())));

            if (isset($domainResponse->Errors->Error)) {
                throw new \Exception($domainResponse->Errors->Error);
            }

            $prices = $domainResponse?->CommandResponse?->UserGetPricingResult?->ProductType?->ProductCategory?->Product?->Price ?? [];

            if (empty($prices)) {
                return '';
            }

            foreach ($prices as $price) {
                if (isset($price?->{'@attributes'}?->Price)) {
                    return (string) $price->{'@attributes'}->Price;
                }

                if (isset($price->Price)) {
                    return (string) $price->Price;
                }
            }

            return '';
        });
    }

    public function searchDomain(string $domain): array
    {
        // Command: namecheap.domains.check
        $url = static::ENOM_URL.'?'.http_build_query([
            // Auth
            'ApiUser' => $this->credential->settings['api_user'],
            'ApiKey' => $this->credential->access_token,
            'UserName' => $this->credential->settings['username'],
            'ClientIp' => $this->credential->settings['client_ip'],
            // command
            'Command' => 'namecheap.domains.check',
            // request deets
            'DomainList' => $domain,
        ]);

        $xmlDebugResponse = cache()->remember($url, now()->addHour(), fn () => Http::get($url)->body());
        $domainResponse = json_decode(json_encode(simplexml_load_string($xmlDebugResponse)));

        if (isset($domainResponse->Errors->Error)) {
            throw new \Exception($domainResponse->Errors->Error);
        }

        $result = $domainResponse->CommandResponse->DomainCheckResult ?? null;

        if ($result === null) {
            throw new \RuntimeException('Missing DomainCheckResult in Enom response');
        }

        $attributes = $result->{'@attributes'} ?? $result;

        return [
            'domain' => (string) ($attributes->Domain ?? $domain),
            'available' => ((string) ($attributes->Available ?? 'false')) === 'true',
            'is_premium' => ((string) ($attributes->IsPremiumName ?? $attributes->IsPremium ?? 'false')) === 'true',
            'price' => isset($attributes->PremiumRegistrationPrice)
                ? (string) $attributes->PremiumRegistrationPrice
                : null,
        ];
    }

    public function registerDomain(string $domain, int $years = 1): array
    {
        [$domainPart, $tld] = explode('.', $domain, 2);

        $url = static::ENOM_URL.'?'.http_build_query([
            // Auth
            'ApiUser' => $this->credential->settings['api_user'],
            'ApiKey' => $this->credential->access_token,
            'UserName' => $this->credential->settings['username'],
            'ClientIp' => $this->credential->settings['client_ip'],
            // command
            'Command' => 'namecheap.domains.create',
            // request deets
            'SLD' => $domainPart,
            'TLD' => $tld,
            'Years' => $years,
        ]);

        $response = Http::get($url)->body();
        $domainResponse = json_decode(json_encode(simplexml_load_string($response)));

        if (isset($domainResponse->Errors->Error)) {
            throw new \Exception($domainResponse->Errors->Error);
        }

        $result = $domainResponse->CommandResponse->DomainCreateResult ?? null;
        $attributes = $result?->{'@attributes'} ?? $result ?? null;

        if (! $attributes) {
            throw new \RuntimeException('Missing DomainCreateResult in Enom response');
        }

        return [
            'domain' => (string) ($attributes->Domain ?? $domain),
            'success' => ((string) ($attributes->IsSuccess ?? 'true')) === 'true',
            'order_id' => isset($attributes->OrderID) ? (string) $attributes->OrderID : null,
            'transaction_id' => isset($attributes->TransactionID) ? (string) $attributes->TransactionID : null,
        ];
    }

    public function renewDomain(string $domain, int $years = 1): array
    {
        [$domainPart, $tld] = explode('.', $domain, 2);

        $url = static::ENOM_URL.'?'.http_build_query([
            // Auth
            'ApiUser' => $this->credential->settings['api_user'],
            'ApiKey' => $this->credential->access_token,
            'UserName' => $this->credential->settings['username'],
            'ClientIp' => $this->credential->settings['client_ip'],
            // command
            'Command' => 'namecheap.domains.renew',
            // request deets
            'SLD' => $domainPart,
            'TLD' => $tld,
            'Years' => $years,
        ]);

        $response = Http::get($url)->body();
        $domainResponse = json_decode(json_encode(simplexml_load_string($response)));

        if (isset($domainResponse->Errors->Error)) {
            throw new \Exception($domainResponse->Errors->Error);
        }

        $result = $domainResponse->CommandResponse->DomainRenewResult ?? null;
        $attributes = $result?->{'@attributes'} ?? $result ?? null;

        if (! $attributes) {
            throw new \RuntimeException('Missing DomainRenewResult in Enom response');
        }

        return [
            'domain' => (string) ($attributes->Domain ?? $domain),
            'success' => ((string) ($attributes->IsSuccess ?? 'true')) === 'true',
            'order_id' => isset($attributes->OrderID) ? (string) $attributes->OrderID : null,
            'transaction_id' => isset($attributes->TransactionID) ? (string) $attributes->TransactionID : null,
        ];
    }
}
