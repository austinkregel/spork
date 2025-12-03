<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use Illuminate\Support\Facades\Http;

class MatrixClient
{
    public function __construct(
        protected string $homeserver = 'matrix.org',
    ) {}

    public function discover()
    {
        $rooms = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.env('MATRIX_ACCESS_TOKEN'),

        ])->get('https://matrix.'.$this->homeserver.'/_matrix/client/v3/joined_rooms')->json('joined_rooms') ?? [];

        $result = [];

        foreach ($rooms as $room) {
            $roomAliases = cache()->rememberForever('room-cache'.$room, fn () => Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.env('MATRIX_ACCESS_TOKEN'),
            ])->get('https://matrix.'.$this->homeserver.'/_matrix/client/v3/rooms/'.$room.'/aliases')->json('aliases') ?? []);

            $result[$room] = $roomAliases;
        }

        return $result;
    }

    public function loginWithPassword(string $username, string $password): array
    {
        $request = Http::withHeaders([
            'Accept' => 'application/json',
        ])->post(config('services.matrix.url').'/_matrix/client/v3/login', [
            'identifier' => [
                'type' => 'm.id.user',
                'user' => $username,
            ],
            'initial_device_display_name' => config('app.name'),
            'password' => $password,
            'type' => 'm.login.password',
        ])->json();

        return $request;
    }

    public function loginWithJwt(string $jwt): array
    {
        $login = Http::withHeaders([
            'Accept' => 'application/json',
        ])->post('https://matrix.'.$this->homeserver.'/_matrix/client/v3/login', [
            'type' => 'org.matrix.login.jwt',
            'token' => $jwt,
        ])->json();

        return $login;
    }

    public function devices(string $jwt): array
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$jwt,
        ])->get('https://matrix.'.$this->homeserver.'/_matrix/client/v3/devices');

        return $response->json('devices') ?? [];
    }

    public function sendMessage(
        string $body,
        string $room,
        string $jwt,
        ?string $inReplyToEvent = null,
    ): array {
        $payload = [
            'msgtype' => 'm.text',
            'body' => $body,
        ];

        if ($inReplyToEvent) {
            $payload['m.relates_to'] = [
                'm.in_reply_to' => [
                    'event_id' => $inReplyToEvent,
                ],
            ];
        }

        return Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$jwt,
        ])->post('https://matrix.'.$this->homeserver.'/_matrix/client/r0/rooms/'.$room.'/send/m.room.message', $payload)->json();
    }
}
