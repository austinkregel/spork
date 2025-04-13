<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkInboxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbox_route_is_accessible()
    {
        $response = $this->get('/-/inbox');

        $response->assertStatus(200);
    }

    public function test_inbox_message_route_is_accessible()
    {
        $messageId = 1; // Replace with a valid message ID
        $response = $this->get("/-/inbox/{$messageId}");

        $response->assertStatus(200);
    }

    public function test_inbox_route_loads_expected_data()
    {
        $response = $this->get('/-/inbox');

        $response->assertInertia(fn ($page) => $page
            ->component('Inbox')
            ->has('messages')
        );
    }

    public function test_inbox_message_route_loads_expected_data()
    {
        $messageId = 1; // Replace with a valid message ID
        $response = $this->get("/-/inbox/{$messageId}");

        $response->assertInertia(fn ($page) => $page
            ->component('InboxMessage')
            ->has('message')
        );
    }
}
