<?php

declare(strict_types=1);

namespace Tests\Feature\Domains;

use App\Contracts\Services\RegistrarServiceContract;
use App\Models\Credential;
use App\Services\Factories\RegistrarServiceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_domains_search_page_renders_with_no_query(): void
    {
        $response = $this->get('http://domains.localhost/');

        $response->assertStatus(200);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Domains/Search')
            ->where('query', '')
        );
    }

    public function test_domains_search_uses_registrar_factory_when_query_present(): void
    {
        $credential = Credential::factory()->create([
            'type' => Credential::TYPE_REGISTRAR,
            'service' => Credential::NAMECHEAP,
        ]);

        $fakeService = new class implements RegistrarServiceContract
        {
            public function getDomains(int $limit = 10, int $page = 1): \Illuminate\Pagination\LengthAwarePaginator
            {
                return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $limit, $page);
            }

            public function getDomainNs(string $domain): array
            {
                return [];
            }

            public function updateDomainNs(string $domain, array $nameservers): array
            {
                return $nameservers;
            }

            public function getTlds(): array
            {
                return [];
            }

            public function searchDomain(string $domain): array
            {
                return [
                    'domain' => $domain,
                    'available' => true,
                    'is_premium' => false,
                    'price' => '9.99',
                ];
            }

            public function registerDomain(string $domain, int $years = 1): array
            {
                return [];
            }

            public function renewDomain(string $domain, int $years = 1): array
            {
                return [];
            }
        };

        app()->instance(RegistrarServiceFactory::class, new class($fakeService) extends RegistrarServiceFactory
        {
            public function __construct(private RegistrarServiceContract $service) {}

            public function make(Credential $credential): RegistrarServiceContract
            {
                return $this->service;
            }
        });

        $response = $this->get('http://domains.localhost/?q=example.com');

        $response->assertStatus(200);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Domains/Search')
            ->where('query', 'example.com')
            ->where('results.domain', 'example.com')
            ->where('results.available', true)
        );
    }
}
