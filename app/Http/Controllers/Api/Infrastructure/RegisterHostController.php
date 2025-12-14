<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Infrastructure;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Infrastructure\RegisterHostRequest;
use App\Http\Resources\Infrastructure\ServerResource;
use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Laravel\Sanctum\NewAccessToken;

class RegisterHostController extends Controller
{
    public function __invoke(RegisterHostRequest $request): JsonResponse
    {
        $credential = $request->credential();
        $payload = $request->validated();

        $machineId = $payload['machine_id'];
        $existing = Server::query()->where('machine_id', $machineId)->first();

        if ($existing && (int) $existing->credential_id !== (int) $credential->id) {
            abort(409, 'Machine id is already registered to a different credential.');
        }

        $attributes = [
            'machine_id' => $machineId,
            'connection_type' => $payload['connection_type'] ?? 'agent',
            'provider_server_id' => $payload['provider_server_id'] ?? null,
            'name' => $payload['name'] ?? ($existing?->name ?? ('host-'.Str::random(6))),
            'status' => $payload['status'] ?? ($existing?->status ?? 'enrolling'),
            'ip_address' => $payload['ip_address'] ?? $existing?->ip_address,
            'ip_address_v6' => $payload['ip_address_v6'] ?? $existing?->ip_address_v6,
            'internal_ip_address' => $payload['internal_ip_address'] ?? $existing?->internal_ip_address,
            'internal_ip_address_v6' => $payload['internal_ip_address_v6'] ?? $existing?->internal_ip_address_v6,
            'os' => $payload['os'] ?? $existing?->os,
            'booted_at' => $payload['booted_at'] ?? $existing?->booted_at,
            'last_ping_at' => now(),
        ];

        $created = false;
        $accessToken = null;

        if (! $existing) {
            $created = true;

            /** @var Server $server */
            $server = $credential->servers()->create(array_merge($attributes, [
                'server_id' => (string) Str::uuid(),
            ]));

            /** @var NewAccessToken $token */
            $token = $server->createToken(
                $server->name.' Access Token',
                ['update_server', 'delete_server', 'view_server'],
            );

            $accessToken = $token->plainTextToken;
        } else {
            $existing->fill($attributes);
            $existing->save();
            $server = $existing->fresh();
        }

        return response()->json([
            'created' => $created,
            'access_token' => $accessToken,
            'server' => new ServerResource($server->loadMissing(['credential', 'providerCredential', 'services', 'domains'])),
        ]);
    }
}


