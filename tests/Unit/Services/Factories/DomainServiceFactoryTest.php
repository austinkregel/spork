<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Factories;

use App\Models\Credential;
use App\Services\Domain\CloudflareDomainService;
use App\Services\Factories\DomainServiceFactory;
use App\Services\Server\DigitalOceanService;
use Tests\TestCase;

class DomainServiceFactoryTest extends TestCase
{
    public function test_it_creates_cloudflare_domain_service_for_cloudflare_credential(): void
    {
        $credential = new Credential([
            'service' => Credential::CLOUDFLARE,
            'api_key' => 'fake global key',
            'settings' => [
                'email' => 'user@example.com',
                'account_id' => 'account',
            ],
            'access_token' => 'fake-token',
        ]);

        $factory = new DomainServiceFactory();

        $service = $factory->make($credential);

        $this->assertInstanceOf(CloudflareDomainService::class, $service);
    }

    public function test_it_creates_digitalocean_domain_service_for_digitalocean_credential(): void
    {
        $credential = new Credential([
            'service' => Credential::DIGITAL_OCEAN,
            'api_key' => 'fake-api-key',
            'access_token' => 'fake-token',
            'settings' => [],
        ]);

        app()->instance(\DigitalOceanV2\Client::class, \Mockery::mock(\DigitalOceanV2\Client::class, function ($mock) {
            $mock->shouldReceive('authenticate')->once();
        }));

        $factory = new DomainServiceFactory();

        $service = $factory->make($credential);

        $this->assertInstanceOf(DigitalOceanService::class, $service);
    }
}


