<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Infrastructure;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Infrastructure\MonitorIngestRequest;
use App\Models\Credential;
use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class MonitorIngestController extends Controller
{
    public function __invoke(MonitorIngestRequest $request): JsonResponse
    {
        $credential = $request->credential();
        $eventType = $request->validated('event_type');
        $payload = $request->validated('payload');

        $clientId = Arr::get($payload, 'clientId')
            ?? Arr::get($payload, 'client_id')
            ?? Arr::get($payload, 'client')
            ?? null;

        if (! is_string($clientId) || $clientId === '') {
            return response()->json([
                'accepted' => false,
                'reason' => 'missing_client_id',
            ], 202);
        }

        /** @var Server|null $server */
        $server = Server::query()->where('name', $clientId)->first();

        if (! $server) {
            $server = $credential->servers()->create([
                'server_id' => (string) Str::uuid(),
                'name' => $clientId,
                'status' => 'unknown',
                'connection_type' => 'agent',
            ]);
        }

        $ip = Arr::get($payload, 'data.ip') ?? Arr::get($payload, 'ip_address') ?? null;
        if (is_string($ip) && $ip !== '') {
            $server->ip_address = $server->ip_address ?: $ip;
        }

        // If the payload includes a status, use it. Otherwise infer online-ish on any event.
        $status = Arr::get($payload, 'data.status') ?? Arr::get($payload, 'status') ?? null;
        if (is_string($status) && $status !== '') {
            $server->status = $status;
        } elseif (in_array($eventType, ['stats', 'agent_stats', 'net_status', 'pong', 'client_list'], true)) {
            $server->status = $server->status === 'unknown' ? 'online' : $server->status;
        }

        $server->last_ping_at = now();
        $server->telemetry = [
            'event_type' => $eventType,
            'payload' => $payload,
            'received_at' => $request->validated('received_at') ?? now()->toIso8601String(),
        ];
        $server->save();

        return response()->json([
            'accepted' => true,
            'server_id' => $server->id,
        ], 202);
    }
}


