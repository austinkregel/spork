<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Factories;

use App\Models\Credential;
use App\Services\Factories\ServerServiceFactory;
use App\Services\Server\DigitalOceanService;
use DigitalOceanV2\Client;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ServerServiceFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_creates_digitalocean_service(): void
    {
        $credential = new Credential([
            'service' => Credential::DIGITAL_OCEAN,
            'api_key' => 'fake-api-key',
        ]);

        app()->instance(Client::class, Mockery::mock(Client::class, function ($mock): void {
            $mock->shouldReceive('authenticate')->once();
        }));

        $factory = new ServerServiceFactory();

        $service = $factory->make($credential);

        $this->assertInstanceOf(DigitalOceanService::class, $service);
    }

    public function test_it_throws_for_unknown_provider(): void
    {
        $credential = new Credential([
            'service' => 'unsupported-provider',
        ]);

        $factory = new ServerServiceFactory();

        $this->expectException(RuntimeException::class);

        $factory->make($credential);
    }
}

