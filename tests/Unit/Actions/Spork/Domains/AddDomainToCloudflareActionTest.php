<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Spork\Domains;

use App\Actions\Spork\Domains\AddDomainToCloudflareAction;
use App\Models\Credential;
use App\Models\Domain;
use App\Models\User;
use App\Services\Factories\DomainServiceFactory;
use App\Services\Factories\RegistrarServiceFactory;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class AddDomainToCloudflareActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_domain_to_cloudflare(): void
    {
        $user = User::factory()->create();
        $credential = Credential::factory()->create([
            'user_id' => $user->id,
            'service' => 'cloudflare',
            'type' => Credential::TYPE_DOMAIN,
        ]);
        $domain = Domain::factory()->create([
            'credential_id' => $credential->id,
        ]);

        $request = Request::create('/add-domain', 'POST', [
            'items' => [$domain->id],
        ]);
        $request->setUserResolver(fn () => $user);

        $dispatcher = Mockery::mock(Dispatcher::class);
        $domainServiceFactory = Mockery::mock(DomainServiceFactory::class);
        $registrarServiceFactory = Mockery::mock(RegistrarServiceFactory::class);

        $cloudflareService = Mockery::mock();
        $cloudflareService->shouldReceive('createDomain')
            ->once()
            ->with($domain->name)
            ->andReturn(['ns1.cloudflare.com', 'ns2.cloudflare.com']);

        $domainServiceFactory->shouldReceive('make')
            ->once()
            ->with($credential)
            ->andReturn($cloudflareService);

        $registrarService = Mockery::mock();
        $registrarService->shouldReceive('updateDomainNs')
            ->once()
            ->with($domain->name, ['ns1.cloudflare.com', 'ns2.cloudflare.com']);

        $registrarServiceFactory->shouldReceive('make')
            ->once()
            ->with($domain->credential)
            ->andReturn($registrarService);

        $action = new AddDomainToCloudflareAction();
        $action->__invoke($dispatcher, $request);
    }
}
