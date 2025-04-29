<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Credential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkDomainsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_domains_route_is_accessible()
    {
        $this->actingAsUser();
        $domain = \App\Models\Domain::factory()->create([
            'credential_id' => Credential::factory()->create([
                'user_id' => $this->user->id,
            ])->id
        ]);

        $response = $this->get('http://spork.localhost/-/domains/' . $domain->id);

        $response->assertStatus(200);
    }

    public function test_domains_route_loads_expected_data()
    {
        $this->actingAsUser();
        $domain = \App\Models\Domain::factory()->create([
            'credential_id' => Credential::factory()->create([
                'user_id' => $this->user->id,
            ])->id
        ]);

        $response = $this->get('http://spork.localhost/-/domains/' . $domain->id);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Domain')
            ->has('domain')
        );
    }
}
