<?php

declare(strict_types=1);

namespace Tests\Feature\Repositories;

use App\Models\Credential;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\Thread;
use App\Models\User;
use App\Repositories\MatrixClientSyncRepository;
use App\Services\Messaging\Matrix\MatrixEventHandlerRegistry;
use App\Services\Messaging\Matrix\MatrixEventSupport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class MatrixRoomEventHandlersTest extends TestCase
{
    use RefreshDatabase;

    protected MatrixClientSyncRepository $repository;

    protected ?Credential $credential = null;

    protected ?User $user = null;

    protected function setUp(): void
    {
        parent::setUp();

        $logger = app(LoggerInterface::class);
        $registry = app(MatrixEventHandlerRegistry::class);
        $support = app(MatrixEventSupport::class);

        $this->repository = new MatrixClientSyncRepository($logger, $registry, $support);
        $this->user = User::factory()->create();
        $this->credential = Credential::factory()->create([
            'user_id' => $this->user->id,
            'settings' => ['matrix_server' => 'https://matrix.test'],
            'access_token' => 'token',
        ]);
    }

    public function test_it_updates_a_message_when_replace_event_is_received(): void
    {
        $thread = Thread::create([
            'thread_id' => '!room:test',
            'name' => '!room:test',
            'origin_server_ts' => now(),
        ]);

        $originalEvent = $this->messageEvent('$original', 'Original body');

        $this->processRoom([$originalEvent]);

        $message = Message::firstWhere('event_id', '$original');
        $this->assertNotNull($message);
        $this->assertSame('Original body', $message->message);

        $editEvent = $this->messageEvent('$edit', '* Original body', [
            'm.relates_to' => [
                'event_id' => '$original',
                'rel_type' => 'm.replace',
            ],
            'm.new_content' => [
                'body' => 'Edited body',
            ],
        ]);

        $this->processRoom([$editEvent]);

        $message->refresh();
        $this->assertSame('Edited body', $message->message);
    }

    public function test_it_upserts_existing_message_when_reprocessed(): void
    {
        Thread::create([
            'thread_id' => '!room:test',
            'name' => '!room:test',
            'origin_server_ts' => now(),
        ]);

        $event = $this->messageEvent('$original', 'Original body');
        $this->processRoom([$event]);

        $updatedEvent = $event;
        $updatedEvent['content']['body'] = 'Updated body';
        $updatedEvent['content']['formatted_body'] = '<p>Updated body</p>';
        $updatedEvent['origin_server_ts'] = now()->addMinute()->valueOf();

        $this->processRoom([$updatedEvent]);

        $message = Message::firstWhere('event_id', '$original');
        $this->assertDatabaseCount('messages', 1);
        $this->assertSame('Updated body', $message->message);
        $this->assertSame('<p>Updated body</p>', $message->html_message);
    }

    public function test_it_refreshes_thumbnail_when_image_message_is_reprocessed(): void
    {
        Http::fake([
            'https://matrix.test/*' => Http::response('binary', 200),
        ]);

        Thread::create([
            'thread_id' => '!room:test',
            'name' => '!room:test',
            'origin_server_ts' => now(),
        ]);

        $event = $this->messageEvent('$image', 'Image body', [
            'msgtype' => 'm.image',
            'info' => ['mimetype' => 'image/png'],
            'url' => 'mxc://matrix.test/original',
        ]);

        $this->processRoom([$event]);

        $message = Message::firstWhere('event_id', '$image');
        $this->assertSame('/storage/original', $message->thumbnail_url);

        $updatedEvent = $event;
        $updatedEvent['content']['url'] = 'mxc://matrix.test/updated';
        $updatedEvent['origin_server_ts'] = now()->addMinute()->valueOf();

        $this->processRoom([$updatedEvent]);

        $message->refresh();
        $this->assertSame('/storage/updated', $message->thumbnail_url);
    }

    public function test_it_redacts_messages(): void
    {
        Thread::create([
            'thread_id' => '!room:test',
            'name' => '!room:test',
            'origin_server_ts' => now(),
        ]);

        $this->processRoom([$this->messageEvent('$to-redact', 'Message to redact')]);

        $redaction = [
            'type' => 'm.room.redaction',
            'sender' => '@friend:matrix.test',
            'redacts' => '$to-redact',
            'content' => [
                'reason' => 'Cleanup',
            ],
            'origin_server_ts' => now()->valueOf(),
            'event_id' => '$redaction',
        ];

        $this->processRoom([$redaction]);

        $message = Message::firstWhere('event_id', '$to-redact');
        $this->assertSame('🗑️ Cleanup', $message->message);
        $this->assertSame('<i>🗑️ Cleanup</i>', $message->html_message);
    }

    public function test_it_updates_thread_avatar_when_avatar_event_is_processed(): void
    {
        Http::fake([
            'https://matrix.test/*' => Http::response('binary-image', 200),
        ]);

        $thread = Thread::create([
            'thread_id' => '!room:test',
            'name' => '!room:test',
            'origin_server_ts' => now(),
            'settings' => [],
        ]);

        $this->processRoom([[
            'type' => 'm.room.avatar',
            'sender' => '@friend:matrix.test',
            'content' => [
                'url' => 'mxc://matrix.test/abc123',
            ],
            'origin_server_ts' => now()->valueOf(),
            'event_id' => '$avatar',
        ]]);

        $thread->refresh();
        $this->assertSame('/storage/abc123', $thread->settings['avatar_url']);
    }

    public function test_it_stores_canonical_alias(): void
    {
        $thread = Thread::create([
            'thread_id' => '!room:test',
            'name' => '!room:test',
            'origin_server_ts' => now(),
            'settings' => [],
        ]);

        $this->processRoom([[
            'type' => 'm.room.canonical_alias',
            'sender' => '@friend:matrix.test',
            'content' => [
                'alias' => '#room:test',
            ],
            'origin_server_ts' => now()->valueOf(),
            'event_id' => '$alias',
        ]]);

        $thread->refresh();
        $this->assertSame('#room:test', $thread->settings['canonical_alias']);
    }

    public function test_it_stores_power_levels_snapshot(): void
    {
        $thread = Thread::create([
            'thread_id' => '!room:test',
            'name' => '!room:test',
            'origin_server_ts' => now(),
            'settings' => [],
        ]);

        $payload = [
            'users' => [
                '@friend:matrix.test' => 100,
            ],
            'events' => [
                'm.room.message' => 50,
            ],
        ];

        $this->processRoom([[
            'type' => 'm.room.power_levels',
            'sender' => '@friend:matrix.test',
            'content' => $payload,
            'origin_server_ts' => now()->valueOf(),
            'event_id' => '$power',
        ]]);

        $thread->refresh();
        $this->assertSame($payload, $thread->settings['power_levels']);
    }

    public function test_it_handles_already_redacted_messages_without_body(): void
    {
        Thread::create([
            'thread_id' => '!room:test',
            'name' => '!room:test',
            'origin_server_ts' => now(),
        ]);

        $this->processRoom([$this->messageEvent('$original', 'Message to redact later')]);

        $this->processRoom([
            [
                'type' => 'm.room.message',
                'sender' => '@friend:matrix.test',
                'content' => [],
                'origin_server_ts' => now()->addMinute()->valueOf(),
                'event_id' => '$original',
                'unsigned' => [
                    'redacted_because' => [
                        'type' => 'm.room.redaction',
                        'sender' => '@friend:matrix.test',
                        'content' => [
                            'reason' => 'Cleanup',
                        ],
                        'origin_server_ts' => now()->addMinute()->addSecond()->valueOf(),
                        'event_id' => '$redaction',
                        'redacts' => '$original',
                    ],
                ],
            ],
        ]);

        $message = Message::firstWhere('event_id', '$original');
        $this->assertSame('🗑️ Cleanup', $message->message);
        $this->assertSame('<i>🗑️ Cleanup</i>', $message->html_message);
    }

    public function test_it_updates_thread_settings_when_encryption_event_is_processed(): void
    {
        $thread = Thread::create([
            'thread_id' => '!room:test',
            'name' => '!room:test',
            'origin_server_ts' => now(),
        ]);

        $event = [
            'type' => 'm.room.encryption',
            'sender' => '@friend:matrix.test',
            'content' => [
                'algorithm' => 'm.megolm.v1.aes-sha2',
                'rotation_period_ms' => 604800000,
            ],
            'origin_server_ts' => now()->valueOf(),
            'event_id' => '$encryption',
        ];

        $this->processRoom([$event]);

        $thread->refresh();
        $this->assertTrue($thread->settings['encrypted']);
        $this->assertSame('m.megolm.v1.aes-sha2', $thread->settings['algorithm']);
        $this->assertSame(604800000, $thread->settings['rotation_period_ms']);
    }

    public function test_it_persists_reactions_from_matrix_events(): void
    {
        Thread::create([
            'thread_id' => '!room:test',
            'name' => '!room:test',
            'origin_server_ts' => now(),
        ]);

        $this->processRoom([$this->messageEvent('$original', 'Message body')]);

        $reaction = [
            'type' => 'm.reaction',
            'sender' => '@friend:matrix.test',
            'content' => [
                'm.relates_to' => [
                    'event_id' => '$original',
                    'rel_type' => 'm.annotation',
                    'key' => '🔥',
                ],
            ],
            'origin_server_ts' => now()->addSecond()->valueOf(),
            'event_id' => '$reaction',
        ];

        $this->processRoom([$reaction]);

        $stored = MessageReaction::first();
        $this->assertNotNull($stored);
        $this->assertSame('🔥', $stored->emoji);
        $this->assertSame('$reaction', $stored->matrix_event_id);
    }

    public function test_it_removes_reactions_when_redacted(): void
    {
        Thread::create([
            'thread_id' => '!room:test',
            'name' => '!room:test',
            'origin_server_ts' => now(),
        ]);

        $this->processRoom([$this->messageEvent('$original', 'Message body')]);

        $reaction = [
            'type' => 'm.reaction',
            'sender' => '@friend:matrix.test',
            'content' => [
                'm.relates_to' => [
                    'event_id' => '$original',
                    'rel_type' => 'm.annotation',
                    'key' => '🔥',
                ],
            ],
            'origin_server_ts' => now()->addSecond()->valueOf(),
            'event_id' => '$reaction',
        ];

        $this->processRoom([$reaction]);

        $this->processRoom([[
            'type' => 'm.room.redaction',
            'sender' => '@friend:matrix.test',
            'redacts' => '$reaction',
            'content' => [
                'reason' => 'cleanup',
            ],
            'origin_server_ts' => now()->addMinutes(2)->valueOf(),
            'event_id' => '$redaction',
        ]]);

        $this->assertDatabaseCount('message_reactions', 0);
    }

    protected function processRoom(array $events): void
    {
        $this->repository->processRoom('!room:test', [
            'state' => ['events' => []],
            'timeline' => ['events' => $events],
        ], $this->credential, $this->user);
    }

    protected function messageEvent(string $eventId, string $body, array $extraContent = []): array
    {
        return [
            'type' => 'm.room.message',
            'sender' => '@friend:matrix.test',
            'content' => array_merge([
                'body' => $body,
                'msgtype' => 'm.text',
            ], $extraContent),
            'origin_server_ts' => now()->valueOf(),
            'event_id' => $eventId,
        ];
    }
}
