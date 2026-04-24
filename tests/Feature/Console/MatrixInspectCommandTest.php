<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Contracts\Services\Messaging\MatrixServiceContract;
use App\Models\Credential;
use App\Models\Message;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MatrixInspectCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_reprocesses_event_and_stores_message(): void
    {
        $user = User::factory()->create();
        Credential::factory()->create([
            'user_id' => $user->id,
            'type' => Credential::TYPE_MATRIX,
            'service' => Credential::TYPE_MATRIX,
            'settings' => ['matrix_server' => 'https://matrix.test'],
            'access_token' => 'token',
        ]);

        Thread::create([
            'thread_id' => '!room:test',
            'name' => '!room:test',
            'origin_server_ts' => now(),
        ]);

        $event = [
            'type' => 'm.room.message',
            'room_id' => '!room:test',
            'sender' => '@friend:matrix.test',
            'content' => [
                'body' => 'Hello from Matrix',
                'msgtype' => 'm.text',
            ],
            'origin_server_ts' => now()->valueOf(),
            'event_id' => '$event',
        ];

        $service = Mockery::mock(MatrixServiceContract::class);
        $service->shouldReceive('fetchEvent')->once()->with('$event')->andReturn($event);
        $this->app->instance(MatrixServiceContract::class, $service);

        $this->artisan('matrix:inspect', ['eventId' => '$event'])
            ->assertExitCode(0);

        $message = Message::firstWhere('event_id', '$event');
        $this->assertNotNull($message);
        $this->assertSame('Hello from Matrix', $message->message);
    }

    public function test_command_fails_when_event_cannot_be_found(): void
    {
        $service = Mockery::mock(MatrixServiceContract::class);
        $service->shouldReceive('fetchEvent')->once()->with('$missing')->andReturnNull();
        $this->app->instance(MatrixServiceContract::class, $service);

        $this->artisan('matrix:inspect', ['eventId' => '$missing'])
            ->assertExitCode(1);

        $this->assertDatabaseCount('messages', 0);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
