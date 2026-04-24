<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Infrastructure;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Infrastructure\MonitorIngestRequest;
use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class MonitorIngestController extends Controller
{
    public function __invoke(MonitorIngestRequest $request): JsonResponse
    {
        try {
            $credential = $request->credential();
            $eventType = $request->validated('event_type');
            $payload = $request->validated('payload');

            \Log::info('monitor.ingest.received', [
                'event_type' => $eventType,
                'credential_id' => $credential->id,
                'has_payload' => ! empty($payload),
            ]);

            $clientId = Arr::get($payload, 'clientId')
                ?? Arr::get($payload, 'client_id')
                ?? Arr::get($payload, 'client')
                ?? null;

            if (! is_string($clientId) || $clientId === '') {
                \Log::warning('monitor.ingest.rejected', [
                    'reason' => 'missing_client_id',
                    'event_type' => $eventType,
                    'credential_id' => $credential->id,
                ]);

                return response()->json([
                    'accepted' => false,
                    'reason' => 'missing_client_id',
                ], 202);
            }

            $machineId = Arr::get($payload, 'data.machine_id')
                ?? Arr::get($payload, 'data.machineId')
                ?? Arr::get($payload, 'machine_id')
                ?? Arr::get($payload, 'machineId')
                ?? null;

            $providerServerId = Arr::get($payload, 'data.provider_server_id')
                ?? Arr::get($payload, 'data.providerServerId')
                ?? Arr::get($payload, 'provider_server_id')
                ?? Arr::get($payload, 'providerServerId')
                ?? null;

            $netIfaces = Arr::get($payload, 'data.netIfaces')
                ?? Arr::get($payload, 'data.net_ifaces')
                ?? Arr::get($payload, 'netIfaces')
                ?? Arr::get($payload, 'net_ifaces')
                ?? [];

            $publicIpv4 = null;
            $internalIpv4 = null;
            $publicIpv6 = null;
            $internalIpv6 = null;

            if (is_array($netIfaces)) {
                foreach ($netIfaces as $iface) {
                    if (! is_array($iface)) {
                        continue;
                    }

                    $family = Arr::get($iface, 'family');
                    $address = Arr::get($iface, 'address');
                    $internal = Arr::get($iface, 'internal');

                    if (! is_string($family) || ! is_string($address) || $address === '') {
                        continue;
                    }

                    if ($family === 'IPv4' && $internal === false && ! is_string($publicIpv4)) {
                        $publicIpv4 = $address;

                        continue;
                    }

                    if ($family === 'IPv4' && $internal === true && ! is_string($internalIpv4)) {
                        $internalIpv4 = $address;

                        continue;
                    }

                    if ($family === 'IPv6' && $internal === false && ! is_string($publicIpv6)) {
                        $publicIpv6 = $address;

                        continue;
                    }

                    if ($family === 'IPv6' && $internal === true && ! is_string($internalIpv6)) {
                        $internalIpv6 = $address;

                        continue;
                    }
                }
            }

            $ip = Arr::get($payload, 'data.ip')
                ?? Arr::get($payload, 'data.ip_address')
                ?? Arr::get($payload, 'ip_address')
                ?? $publicIpv4
                ?? $internalIpv4
                ?? $publicIpv6
                ?? $internalIpv6
                ?? null;

            /** @var Server|null $server */
            $server = null;

            // Resolve server by stable identity first to avoid duplicates:
            // 1) machine_id (unique), 2) provider_server_id, 3) IP, 4) clientId/hostname
            if (is_string($machineId) && $machineId !== '') {
                $server = Server::query()->where('machine_id', $machineId)->first();
            }

            if (! $server && is_string($providerServerId) && $providerServerId !== '') {
                $server = Server::query()->where('provider_server_id', $providerServerId)->first();
            }

            if (! $server && is_string($ip) && $ip !== '') {
                $server = Server::query()
                    ->where(function ($q) use ($ip): void {
                        $q->where('ip_address', $ip)
                            ->orWhere('internal_ip_address', $ip)
                            ->orWhere('ip_address_v6', $ip)
                            ->orWhere('internal_ip_address_v6', $ip);
                    })
                    ->first();
            }

            if (! $server) {
                $server = Server::query()->where('name', $clientId)->first();
            }

            $wasNew = false;
            if (! $server) {
                $wasNew = true;
                $server = $credential->servers()->create([
                    'server_id' => (string) Str::uuid(),
                    'name' => $clientId,
                    'status' => 'unknown',
                    'connection_type' => 'agent',
                    'machine_id' => is_string($machineId) && $machineId !== '' ? $machineId : null,
                ]);

                \Log::info('monitor.ingest.server.created', [
                    'server_id' => $server->id,
                    'client_id' => $clientId,
                    'machine_id' => $machineId,
                    'event_type' => $eventType,
                    'credential_id' => $credential->id,
                ]);
            } else {
                \Log::debug('monitor.ingest.server.found', [
                    'server_id' => $server->id,
                    'client_id' => $clientId,
                    'machine_id' => $machineId,
                    'event_type' => $eventType,
                    'credential_id' => $credential->id,
                    'matched_by' => $this->getServerMatchMethod($server, $machineId, $providerServerId, $ip, $clientId),
                ]);
            }

            if (is_string($ip) && $ip !== '') {
                // Never clobber an existing address (provider should remain authoritative).
                $server->ip_address = $server->ip_address ?: $ip;
            }

            // Hydrate IP fields from stats payloads, without clobbering existing values.
            // This allows the UI to rely on stable model fields rather than parsing telemetry.
            $server->ip_address = $server->ip_address ?: $publicIpv4;
            $server->internal_ip_address = $server->internal_ip_address ?: $internalIpv4;
            $server->ip_address_v6 = $server->ip_address_v6 ?: $publicIpv6;
            $server->internal_ip_address_v6 = $server->internal_ip_address_v6 ?: $internalIpv6;

            // Fill machine_id if provided, without violating uniqueness.
            if (is_string($machineId) && $machineId !== '' && (! is_string($server->machine_id) || $server->machine_id === '')) {
                $conflict = Server::query()
                    ->where('machine_id', $machineId)
                    ->whereKeyNot($server->getKey())
                    ->exists();

                if (! $conflict) {
                    $server->machine_id = $machineId;
                }
            }

            $isProviderBacked = (bool) ($server->provider_credential_id || $server->provider_server_id || $server->connection_type === 'provider');

            // If the payload includes a status, use it (unless provider-backed). Otherwise infer online-ish on known heartbeat events.
            $status = Arr::get($payload, 'data.status') ?? Arr::get($payload, 'status') ?? null;
            if (! $isProviderBacked) {
                if (is_string($status) && $status !== '') {
                    $server->status = $status;
                } elseif (in_array($eventType, ['stats', 'agent_stats', 'net_status', 'pong', 'client_list'], true)) {
                    $server->status = $server->status === 'unknown' ? 'online' : $server->status;
                }
            } elseif ($server->status === 'unknown' && in_array($eventType, ['stats', 'agent_stats', 'net_status', 'pong', 'client_list'], true)) {
                // Minimal provider-safe update: only promote unknown -> online-ish.
                $server->status = 'online';
            }

            $server->last_ping_at = now();
            $server->telemetry = [
                'event_type' => $eventType,
                'payload' => $payload,
                'received_at' => $request->validated('received_at') ?? now()->toIso8601String(),
            ];

            $hadChanges = $server->isDirty();
            $server->save();

            \Log::info('monitor.ingest.server.updated', [
                'server_id' => $server->id,
                'client_id' => $clientId,
                'event_type' => $eventType,
                'was_new' => $wasNew,
                'had_changes' => $hadChanges,
                'status' => $server->status,
                'ip_address' => $server->ip_address,
                'machine_id' => $server->machine_id,
                'credential_id' => $credential->id,
            ]);

            return response()->json([
                'accepted' => true,
                'server_id' => $server->id,
            ], 202);
        } catch (\Throwable $e) {
            \Log::error('monitor.ingest.error', [
                'message' => $e->getMessage(),
                'event_type' => $request->validated('event_type'),
                'credential_id' => $request->credential()->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'accepted' => false,
                'reason' => 'processing_error',
            ], 500);
        }
    }

    /**
     * Determine how the server was matched (for logging/debugging).
     */
    private function getServerMatchMethod(
        Server $server,
        ?string $machineId,
        ?string $providerServerId,
        ?string $ip,
        string $clientId
    ): string {
        if ($machineId && $server->machine_id === $machineId) {
            return 'machine_id';
        }
        if ($providerServerId && $server->provider_server_id === $providerServerId) {
            return 'provider_server_id';
        }
        if ($ip && (
            $server->ip_address === $ip
            || $server->internal_ip_address === $ip
            || $server->ip_address_v6 === $ip
            || $server->internal_ip_address_v6 === $ip
        )) {
            return 'ip_address';
        }
        if ($server->name === $clientId) {
            return 'name';
        }

        return 'unknown';
    }
}
