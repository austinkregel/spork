<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Credential;
use App\Models\Message;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkInboxControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['broadcasting.default' => 'null']);
    }

    public function test_chat_route_is_accessible(): void
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/chat');

        $response->assertStatus(200);
    }

    public function test_chat_thread_route_is_accessible(): void
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
                'service' => 'imap',
            ])->id,
            'thread_id' => $thread->id,
        ])->thread_id;

        $response = $this->get("http://spork.localhost/-/chat/$thread->id");

        $response->assertStatus(200);
    }

    public function test_chat_route_loads_expected_data(): void
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/chat');

        $response->assertInertia(fn ($page) => $page
            ->component('Conversations/Hub')
            ->has('threads')
            ->has('activeThread')
        );
    }

    public function test_inbox_route_only_lists_threads_for_authenticated_user(): void
    {
        $this->actingAsUser();

        $myThread = Thread::factory()->create();
        $otherThread = Thread::factory()->create();

        $myThread->participants()->attach($this->user->person->id, ['joined_at' => now()]);

        $otherUser = User::factory()->create();
        $otherThread->participants()->attach($otherUser->person->id, ['joined_at' => now()]);

        $this->createMessageForThread($myThread);
        $this->createMessageForThread($otherThread, $otherUser);

        $response = $this->get('http://spork.localhost/-/chat');

        $response->assertInertia(fn ($page) => $page
            ->component('Conversations/Hub')
            ->has('threads.data', 1)
            ->where('threads.data.0.id', $myThread->id)
        );
    }

    public function test_chat_thread_route_loads_expected_data(): void
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
        $response = $this->get("http://spork.localhost/-/chat/{$thread->id}");

        $response->assertStatus(200);
    }

    protected function createMessageForThread(Thread $thread, ?User $user = null): void
    {
        $user ??= $this->user;

        $credentialId = Credential::factory()->create([
            'name' => 'Test Credential',
            'type' => 'email',
            'service' => 'imap',
            'user_id' => $user->id,
        ])->id;

        Message::factory()->create([
            'credential_id' => $credentialId,
            'thread_id' => $thread->id,
        ]);
    }
}
