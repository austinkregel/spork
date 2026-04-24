<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Registrar;

use App\Models\Credential;
use App\Services\Registrar\CloudflareRegistrarService;
use Carbon\Carbon;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CloudflareRegistrarServiceTest extends TestCase
{
    protected function makeCredential(): Credential
    {
        return new Credential([
            'api_key' => 'fake global key',
            'access_token' => 'fake-token',
            'settings' => [
                'email' => 'user@example.com',
                'account_id' => 'account-123',
            ],
        ]);
    }

    public function test_get_domains_returns_paginator_with_mapped_domains(): void
    {
        Http::fake([
            CloudflareRegistrarService::CLOUDFLARE_URL.'accounts/*/registrar/domains*' => Http::response([
                'success' => true,
                'result' => [
                    [
                        'registry_object_id' => 'reg-1',
                        'name' => 'example.com',
                        'expires_at' => '2030-01-01T00:00:00Z',
                        'locked' => true,
                        'auto_renew' => false,
                        'privacy' => true,
                    ],
                ],
                'result_info' => [
                    'total_count' => 1,
                    'per_page' => 100,
                ],
            ], 200),
        ]);

        $service = new CloudflareRegistrarService($this->makeCredential());

        $paginator = $service->getDomains(100, 2);

        $this->assertSame(1, $paginator->total());
        $this->assertCount(1, $paginator->items());

        $domain = $paginator->items()[0];
        $this->assertSame('reg-1', $domain['id']);
        $this->assertSame('example.com', $domain['domain']);
        $this->assertInstanceOf(Carbon::class, $domain['expires_at']);
        $this->assertSame(false, $domain['is_expired']);

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && str_contains((string) $request->url(), '/accounts/account-123/registrar/domains')
                && $request['per_page'] === 100
                && $request['page'] === 2;
        });
    }

    public function test_get_domains_throws_when_response_is_unsuccessful(): void
    {
        Http::fake([
            CloudflareRegistrarService::CLOUDFLARE_URL.'accounts/*/registrar/domains*' => Http::response([
                'success' => false,
            ], 500),
        ]);

        $service = new CloudflareRegistrarService($this->makeCredential());

        $this->expectException(RequestException::class);

        $service->getDomains();
    }

    public function test_it_requires_email_account_id_and_access_token(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new CloudflareRegistrarService(new Credential([
            'api_key' => 'fake global key',
            'access_token' => '',
            'settings' => [
                'email' => '',
                'account_id' => '',
            ],
        ]));
    }

    public function test_get_domain_ns_returns_current_nameservers(): void
    {
        Http::fake([
            CloudflareRegistrarService::CLOUDFLARE_URL.'accounts/*' => Http::response([
                'success' => true,
                'result' => [
                    'current_nameservers' => [
                        'ns1.example.com',
                        'ns2.example.com',
                    ],
                ],
            ], 200),
        ]);

        $service = new CloudflareRegistrarService($this->makeCredential());

        $nameservers = $service->getDomainNs('example.com');

        $this->assertSame(['ns1.example.com', 'ns2.example.com'], $nameservers);
    }

    public function test_get_domain_ns_throws_when_response_is_unsuccessful(): void
    {
        Http::fake([
            CloudflareRegistrarService::CLOUDFLARE_URL.'accounts/*' => Http::response([], 500),
        ]);

        $service = new CloudflareRegistrarService($this->makeCredential());

        $this->expectException(\RuntimeException::class);

        $service->getDomainNs('example.com');
    }

    public function test_get_domain_ns_throws_when_missing_nameservers(): void
    {
        Http::fake([
            CloudflareRegistrarService::CLOUDFLARE_URL.'accounts/*' => Http::response([
                'success' => true,
                'result' => [],
            ], 200),
        ]);

        $service = new CloudflareRegistrarService($this->makeCredential());

        $this->expectException(\RuntimeException::class);

        $service->getDomainNs('example.com');
    }

    public function test_update_domain_ns_sends_put_and_returns_nameservers(): void
    {
        Http::fake([
            CloudflareRegistrarService::CLOUDFLARE_URL.'accounts/*' => Http::response([
                'success' => true,
            ], 200),
        ]);

        $service = new CloudflareRegistrarService($this->makeCredential());

        $nameservers = ['ns1.example.com', 'ns2.example.com'];

        $result = $service->updateDomainNs('example.com', $nameservers);

        $this->assertSame($nameservers, $result);

        Http::assertSent(function ($request) use ($nameservers) {
            return $request->method() === 'PUT'
                && str_contains((string) $request->url(), '/accounts/account-123/registrar/domains/example.com/nameservers')
                && $request['nameservers'] === array_values($nameservers);
        });
    }

    public function test_update_domain_ns_throws_when_response_is_unsuccessful(): void
    {
        Http::fake([
            CloudflareRegistrarService::CLOUDFLARE_URL.'accounts/*' => Http::response([], 500),
        ]);

        $service = new CloudflareRegistrarService($this->makeCredential());

        $this->expectException(\RuntimeException::class);

        $service->updateDomainNs('example.com', ['ns1.example.com']);
    }

    public function test_get_tlds_is_not_implemented(): void
    {
        $service = new CloudflareRegistrarService($this->makeCredential());

        $this->expectException(\BadMethodCallException::class);

        $service->getTlds();
    }

    public function test_search_domain_is_not_implemented(): void
    {
        $service = new CloudflareRegistrarService($this->makeCredential());

        $this->expectException(\BadMethodCallException::class);

        $service->searchDomain('example.com');
    }

    public function test_register_domain_is_not_implemented(): void
    {
        $service = new CloudflareRegistrarService($this->makeCredential());

        $this->expectException(\BadMethodCallException::class);

        $service->registerDomain('example.com', 1);
    }

    public function test_renew_domain_is_not_implemented(): void
    {
        $service = new CloudflareRegistrarService($this->makeCredential());

        $this->expectException(\BadMethodCallException::class);

        $service->renewDomain('example.com', 1);
    }
}
