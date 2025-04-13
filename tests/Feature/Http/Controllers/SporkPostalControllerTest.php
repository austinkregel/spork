<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkPostalControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_postal_route_is_accessible()
    {
        $response = $this->get('/-/postal');

        $response->assertStatus(200);
    }

    public function test_postal_email_route_is_accessible()
    {
        $emailId = 1; // Assuming an email with ID 1 exists for testing
        $response = $this->get("/-/postal/{$emailId}");

        $response->assertStatus(200);
    }

    public function test_postal_route_loads_expected_data()
    {
        $response = $this->get('/-/postal');

        $response->assertInertia(fn ($page) => $page
            ->component('Postal/Index')
            ->has('emails')
        );
    }

    public function test_postal_email_route_loads_expected_data()
    {
        $emailId = 1; // Assuming an email with ID 1 exists for testing
        $response = $this->get("/-/postal/{$emailId}");

        $response->assertInertia(fn ($page) => $page
            ->component('Postal/Show')
            ->has('email')
        );
    }
}
