<?php

declare(strict_types=1);

namespace Tests\Feature\Dav;

use Illuminate\Foundation\Testing\RefreshDatabase;

class DavRoutingTest extends DavTestCase
{
    use RefreshDatabase;

    public function test_well_known_carddav_redirects_to_dav_root(): void
    {
        $response = $this->get('/.well-known/carddav');

        $response->assertStatus(301);
        $response->assertRedirect('/dav/');
    }

    public function test_well_known_caldav_redirects_to_dav_root(): void
    {
        $response = $this->get('/.well-known/caldav');

        $response->assertStatus(301);
        $response->assertRedirect('/dav/');
    }

    public function test_dav_root_returns_401_without_credentials(): void
    {
        $response = $this->davRequest('PROPFIND', '/dav/');

        $response->assertStatus(401);
    }
}
