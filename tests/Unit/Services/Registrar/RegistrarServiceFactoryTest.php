<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Registrar;

use App\Models\Credential;
use App\Services\Factories\RegistrarServiceFactory;
use App\Services\Registrar\CloudflareRegistrarService;
use App\Services\Registrar\NamecheapService;
use App\Services\Registrar\TucowsEnomService;
use Tests\TestCase;

class RegistrarServiceFactoryTest extends TestCase
{
    public function test_it_creates_namecheap_service_for_namecheap_credential(): void
    {
        $credential = new Credential([
            'service' => Credential::NAMECHEAP,
            'settings' => [],
        ]);

        $factory = new RegistrarServiceFactory();

        $service = $factory->make($credential);

        $this->assertInstanceOf(NamecheapService::class, $service);
    }

    public function test_it_creates_cloudflare_service_for_cloudflare_credential(): void
    {
        $credential = new Credential([
            'service' => Credential::CLOUDFLARE,
            'settings' => [
                'email' => 'user@example.com',
                'account_id' => 'account',
            ],
            'access_token' => 'fake-token',
        ]);

        $factory = new RegistrarServiceFactory();

        $service = $factory->make($credential);

        $this->assertInstanceOf(CloudflareRegistrarService::class, $service);
    }

    public function test_it_creates_enom_service_for_enom_credential(): void
    {
        $credential = new Credential([
            'service' => Credential::ENOM,
            'settings' => [
                'api_user' => 'api-user',
                'username' => 'api-user',
                'client_ip' => '127.0.0.1',
            ],
        ]);

        $factory = new RegistrarServiceFactory();

        $service = $factory->make($credential);

        $this->assertInstanceOf(TucowsEnomService::class, $service);
    }
}


