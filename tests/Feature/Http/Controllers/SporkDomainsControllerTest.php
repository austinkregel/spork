<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkDomainsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_domains_route_is_accessible()
    {
        $domain = \App\Models\Domain::factory()->create();

        $response = $this->get('/-/domains/' . $domain->id);

        $response->assertStatus(200);
    }

    public function test_domains_route_loads_expected_data()
    {
        $domain = \App\Models\Domain::factory()->create();

        $response = $this->get('/-/domains/' . $domain->id);

        $response->assertInertia(fn ($page) => $page
            ->component('Domain')
            ->has('domain')
        );
    }
}
