<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Credential;
use App\Models\Message;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkInboxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbox_route_is_accessible()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/inbox');

        $response->assertStatus(200);
    }

    public function test_inbox_message_route_is_accessible()
    {
        $this->actingAsUser();
        $thread = Thread::factory()->create([
            'thread_id' => 8429729,
        ]);

        $thread->participants()->attach([
            $this->user->person->id => [
                'joined_at' => now(),
            ],
        ]);

        $messageId = Message::factory()->create([
            'credential_id' => $this->user->credentials()->create([
                'name' => 'Test Credential',
                'type' => 'email',
                'service' => 'imap'
            ])->id,
            'thread_id' => $thread->id,
        ])->thread_id;

        $response = $this->get("http://spork.localhost/-/inbox/$thread->id");

        $response->assertStatus(200);
    }

    public function test_inbox_route_loads_expected_data()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/inbox');

        $response->assertInertia(fn ($page) => $page
            ->component('Postal/Index')
            ->has('threads')
        );
    }

    public function test_inbox_message_route_loads_expected_data()
    {
        $this->actingAsUser();
        $credentialId = Credential::factory()->create([
            'name' => 'Test Credential',
            'type' => 'email',
            'service' => 'imap',
            'user_id' => $this->user->id,
        ])->id;

        $thread = Thread::factory()->create([
            'thread_id' => 8429729,
        ]);
        $thread->participants()->attach([
            $this->user->person->id => [
                'joined_at' => now(),
            ],
        ]);
        $messageId = Message::factory()->create([
            'credential_id' => $credentialId,
            'thread_id' => $thread->id,
        ])->thread_id;
        $response = $this->get("http://spork.localhost/-/inbox/{$thread->id}");

        $response->assertStatus(200);
    }
}
