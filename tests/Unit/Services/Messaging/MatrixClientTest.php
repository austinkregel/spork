<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Messaging;

use App\Services\Messaging\MatrixClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MatrixClientTest extends TestCase
{
    public function test_discover_returns_room_alias_map(): void
    {
        Http::fake([
            'https://matrix.beeper.com/_matrix/client/v3/joined_rooms' => Http::response([
                'joined_rooms' => ['!room1:beeper.com'],
            ], 200),
            'https://matrix.beeper.com/_matrix/client/v3/rooms/!room1:beeper.com/aliases' => Http::response([
                'aliases' => ['#room:beeper.com'],
            ], 200),
        ]);

        Cache::clear();

        $client = new MatrixClient('beeper.com');

        $result = $client->discover();

        $this->assertSame(['!room1:beeper.com' => ['#room:beeper.com']], $result);
    }

    public function test_devices_returns_devices_array(): void
    {
        Http::fake([
            'https://matrix.beeper.com/_matrix/client/v3/devices' => Http::response([
                'devices' => [
                    ['device_id' => 'ABC', 'display_name' => 'Device'],
                ],
            ], 200),
        ]);

        $client = new MatrixClient('beeper.com');

        $devices = $client->devices('jwt-token');

        $this->assertSame([
            ['device_id' => 'ABC', 'display_name' => 'Device'],
        ], $devices);
    }
}


