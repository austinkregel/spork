<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkNotificationsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_route_is_accessible()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/notifications');

        $response->assertStatus(200);
    }

    public function test_notifications_route_loads_expected_data()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/notifications');

        $response->assertInertia(fn ($page) => $page
            ->component('Notifications')
            ->has('notifications')
        );
    }
}
