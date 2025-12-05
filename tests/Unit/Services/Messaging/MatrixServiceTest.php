<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Messaging;

use App\Contracts\Services\Messaging\MatrixServiceContract;
use App\Models\Credential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MatrixServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_fetch_event_returns_payload_when_available(): void
    {
        Credential::factory()->create([
            'type' => Credential::TYPE_MATRIX,
            'service' => Credential::TYPE_MATRIX,
            'settings' => [
                'matrix_server' => 'https://matrix.test',
            ],
            'access_token' => 'matrix-token',
        ]);

        Http::fake([
            'https://matrix.test/_matrix/client/v3/events/%24event123' => Http::response([
                'event_id' => '$event123',
                'content' => ['body' => 'hello world'],
            ], 200),
        ]);

        $service = app(MatrixServiceContract::class);

        $event = $service->fetchEvent('$event123');

        $this->assertSame([
            'event_id' => '$event123',
            'content' => ['body' => 'hello world'],
        ], $event);
    }

    public function test_fetch_event_returns_null_when_event_missing(): void
    {
        Credential::factory()->create([
            'type' => Credential::TYPE_MATRIX,
            'service' => Credential::TYPE_MATRIX,
            'settings' => [
                'matrix_server' => 'https://matrix.test',
            ],
            'access_token' => 'matrix-token',
        ]);

        Http::fake([
            'https://matrix.test/_matrix/client/v3/events/%24missing' => Http::response([], 404),
        ]);

        $service = app(MatrixServiceContract::class);

        $this->assertNull($service->fetchEvent('$missing'));
    }

    public function test_fetch_event_returns_null_without_matrix_credentials(): void
    {
        $service = app(MatrixServiceContract::class);

        $this->assertNull($service->fetchEvent('$event123'));
    }
}


