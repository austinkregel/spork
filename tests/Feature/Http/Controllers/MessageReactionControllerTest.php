<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Credential;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\Person;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MessageReactionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_reaction_to_message(): void
    {
        Http::fake([
            'https://matrix.matrix.test/*' => Http::response(['event_id' => '$reaction'], 200),
        ]);

        $user = User::factory()->create();
        $person = Person::factory()->create([
            'user_id' => $user->id,
            'identifiers' => ['@tester:matrix.test'],
            'emails' => [$user->email],
        ]);
        $user->setRelation('person', $person);

        Credential::factory()->create([
            'user_id' => $user->id,
            'service' => 'matrix',
            'access_token' => 'token',
            'settings' => ['matrix_server' => 'https://matrix.matrix.test'],
        ]);

        $thread = Thread::factory()->create([
            'thread_id' => '!room:test',
            'origin_server_ts' => now(),
        ]);
        $thread->participants()->attach($person, ['joined_at' => now()]);

        $message = Message::factory()
            ->for($thread)
            ->create([
                'thread_id' => $thread->id,
                'event_id' => '$message',
                'message' => 'Hello world',
            ]);

        $this->actingAs($user)
            ->postJson(route('api.chat.messages.reactions.store', $message), ['emoji' => '🔥'])
            ->assertOk()
            ->assertJsonPath('reaction.emoji', '🔥');

        $this->assertDatabaseHas('message_reactions', [
            'message_id' => $message->id,
            'person_id' => $person->id,
            'emoji' => '🔥',
            'matrix_event_id' => '$reaction',
        ]);
    }

    public function test_user_can_remove_reaction_from_message(): void
    {
        Http::fake([
            'https://matrix.matrix.test/*' => Http::response([], 200),
        ]);

        $user = User::factory()->create();
        $person = Person::factory()->create([
            'user_id' => $user->id,
            'identifiers' => ['@tester:matrix.test'],
            'emails' => [$user->email],
        ]);
        $user->setRelation('person', $person);

        Credential::factory()->create([
            'user_id' => $user->id,
            'service' => 'matrix',
            'access_token' => 'token',
            'settings' => ['matrix_server' => 'https://matrix.matrix.test'],
        ]);

        $thread = Thread::factory()->create([
            'thread_id' => '!room:test',
            'origin_server_ts' => now(),
        ]);
        $thread->participants()->attach($person, ['joined_at' => now()]);

        $message = Message::factory()
            ->for($thread)
            ->create([
                'thread_id' => $thread->id,
                'event_id' => '$message',
                'message' => 'Hello world',
            ]);

        $reaction = MessageReaction::factory()->for($message)->create([
            'person_id' => $person->id,
            'emoji' => '🔥',
            'matrix_event_id' => '$reaction',
        ]);

        $this->actingAs($user)
            ->deleteJson(route('api.chat.messages.reactions.destroy', $message), ['emoji' => '🔥'])
            ->assertOk()
            ->assertJson(['status' => 'removed']);

        $this->assertDatabaseMissing('message_reactions', ['id' => $reaction->id]);
    }
}
