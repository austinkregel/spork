<?php

declare(strict_types=1);

namespace Tests\Unit\Events\Models\Message;

use App\Events\Models\Message\MessageCreated;
use App\Models\Message;
use App\Models\Person;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageCreatedBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_broadcasts_sender_and_preview_payload(): void
    {
        $sender = Person::factory()->create([
            'name' => 'Alice',
            'photo_url' => 'https://example.com/alice.jpg',
        ]);

        $thread = Thread::factory()->create([
            'name' => 'Demo thread',
        ]);

        $message = Message::factory()->create([
            'from_person' => $sender->id,
            'is_decrypted' => true,
            'message' => 'Hello world from Alice',
            'thread_id' => $thread->id,
        ]);

        $payload = (new MessageCreated($message))->broadcastWith();

        $this->assertSame($message->thread_id, $payload['thread_id']);
        $this->assertSame($message->event_id, $payload['event_id']);
        $this->assertSame('Hello world from Alice', $payload['preview']);
        $this->assertIsArray($payload['from_person']);
        $this->assertSame($sender->id, $payload['from_person']['id']);
        $this->assertSame('Alice', $payload['from_person']['name']);
        $this->assertSame('https://example.com/alice.jpg', $payload['from_person']['photo_url']);
        $this->assertSame('Demo thread', $payload['thread_name']);
    }

    public function test_it_includes_reply_to_preview_when_present(): void
    {
        $sender = Person::factory()->create(['name' => 'Alice']);
        $originalAuthor = Person::factory()->create(['name' => 'Bob']);

        $replyTo = Message::factory()->create([
            'from_person' => $originalAuthor->id,
            'is_decrypted' => true,
            'message' => 'Original message body',
        ]);

        $message = Message::factory()->create([
            'from_person' => $sender->id,
            'is_decrypted' => true,
            'message' => 'Reply body',
            'reply_to_message_id' => $replyTo->id,
            'thread_id' => $replyTo->thread_id,
        ]);

        $payload = (new MessageCreated($message))->broadcastWith();

        $this->assertIsArray($payload['reply_to']);
        $this->assertSame($replyTo->event_id, $payload['reply_to']['event_id']);
        $this->assertSame('Original message body', $payload['reply_to']['preview']);
        $this->assertSame('Bob', $payload['reply_to']['from_person']['name']);
    }
}
