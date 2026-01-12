<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Credential;
use App\Services\Server\DigitalOceanService as ConcreteDigitalOceanService;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;

class DigitalOceanServiceTest extends TestCase
{
    protected function makeCredential(): Credential
    {
        return new Credential([
            'api_key' => 'fake-api-key',
            'settings' => [],
        ]);
    }

    public function test_get_domain_ns_uses_dns_records(): void
    {
        $service = Mockery::mock(
            ConcreteDigitalOceanService::class.'[getDns]',
            [$this->makeCredential()]
        )->makePartial();

        $paginator = new LengthAwarePaginator(
            [
                ['type' => 'NS', 'data' => 'ns1.example.com'],
                ['type' => 'NS', 'data' => 'ns2.example.com'],
            ],
            2,
            10,
            1
        );

        $service->shouldReceive('getDns')
            ->once()
            ->with('example.com', 'NS', 1000, 1)
            ->andReturn($paginator);

        $nameservers = $service->getDomainNs('example.com');

        $this->assertSame(['ns1.example.com', 'ns2.example.com'], $nameservers);
    }

    public function test_update_domain_ns_replaces_ns_records(): void
    {
        $service = Mockery::mock(
            ConcreteDigitalOceanService::class.'[getDns,deleteDnsRecord,createDnsRecord]',
            [$this->makeCredential()]
        )->makePartial();

        $paginator = new LengthAwarePaginator(
            [
                ['id' => 1, 'type' => 'NS', 'data' => 'old1.example.com'],
                ['id' => 2, 'type' => 'NS', 'data' => 'old2.example.com'],
            ],
            2,
            10,
            1
        );

        $service->shouldReceive('getDns')
            ->once()
            ->with('example.com', 'NS', 1000, 1)
            ->andReturn($paginator);

        $service->shouldReceive('deleteDnsRecord')
            ->once()->with('example.com', '1');

        $service->shouldReceive('deleteDnsRecord')
            ->once()->with('example.com', '2');

        $service->shouldReceive('createDnsRecord')
            ->once()->with('example.com', [
                'type' => 'NS',
                'name' => '@',
                'data' => 'ns1.example.com',
            ]);

        $service->shouldReceive('createDnsRecord')
            ->once()->with('example.com', [
                'type' => 'NS',
                'name' => '@',
                'data' => 'ns2.example.com',
            ]);

        $nameservers = ['ns1.example.com', 'ns2.example.com'];

        $result = $service->updateDomainNs('example.com', $nameservers);

        $this->assertSame($nameservers, $result);
    }

    public function test_create_domain_returns_default_nameservers_array(): void
    {
        $service = Mockery::mock(
            ConcreteDigitalOceanService::class.'[createDomain]',
            [$this->makeCredential()]
        )->makePartial();

        $expected = [
            'ns1.digitalocean.com',
            'ns2.digitalocean.com',
            'ns3.digitalocean.com',
        ];

        $service->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createDomain')
            ->once()
            ->with('example.com')
            ->andReturn($expected);

        $this->assertSame($expected, $service->createDomain('example.com'));
    }
}
