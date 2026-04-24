<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Domain;

use App\Models\Credential;
use App\Services\Domain\CloudflareDomainService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CloudflareDomainServiceTest extends TestCase
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

    public function test_get_dns_returns_paginator(): void
    {
        Http::fake([
            CloudflareDomainService::CLOUDFLARE_URL.'*' => Http::response([
                'success' => true,
                'result' => [
                    [
                        'id' => 'dns-1',
                        'name' => 'example.com',
                        'type' => 'A',
                        'content' => '1.2.3.4',
                        'ttl' => 120,
                        'priority' => null,
                        'proxied' => false,
                    ],
                ],
                'result_info' => [
                    'total_count' => 1,
                    'per_page' => 10,
                ],
            ], 200),
        ]);

        $service = new CloudflareDomainService($this->makeCredential());

        $paginator = $service->getDns('zone-123');

        $this->assertSame(1, $paginator->total());
        $this->assertCount(1, $paginator->items());
        $this->assertSame('dns-1', $paginator->items()[0]['id']);
    }

    public function test_get_dns_throws_when_result_is_missing(): void
    {
        Http::fake([
            CloudflareDomainService::CLOUDFLARE_URL.'*' => Http::response([
                'success' => false,
            ], 500),
        ]);

        $service = new CloudflareDomainService($this->makeCredential());

        $this->expectException(RequestException::class);

        $service->getDns('zone-123');
    }
}
