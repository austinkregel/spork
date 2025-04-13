<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkNotificationsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_route_is_accessible()
    {
        $response = $this->get('/-/notifications');

        $response->assertStatus(200);
    }

    public function test_notifications_route_loads_expected_data()
    {
        $response = $this->get('/-/notifications');

        $response->assertInertia(fn ($page) => $page
            ->component('Notifications')
            ->has('notifications')
        );
    }
}
