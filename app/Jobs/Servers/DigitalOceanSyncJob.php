<?php

declare(strict_types=1);

namespace App\Jobs\Servers;

use App\Models\Domain;
use App\Models\Server;
use App\Models\ServerService;
use Illuminate\Support\Arr;

class DigitalOceanSyncJob extends AbstractSyncServerResourceJob
{
    public function sync(): void
    {
        // this means all servers need to respond with the keys.
        $servers = $this->service->findAllServers();

        $remoteServerIds = collect($servers)
            ->map(fn (array $server) => (string) Arr::get($server, 'id', ''))
            ->filter(fn (string $id) => $id !== '')
            ->values()
            ->all();

        foreach ($servers as $server) {
            $providerServerId = (string) Arr::get($server, 'id', '');
            if ($providerServerId === '') {
                continue;
            }

            // If prior sync runs created duplicates, collapse them safely.
            $dupes = Server::query()
                ->where('credential_id', $this->credential->id)
                ->where('server_id', $providerServerId)
                ->orderBy('id')
                ->get();

            /** @var Server|null $canonical */
            $canonical = $dupes->first();

            if ($canonical && $dupes->count() > 1) {
                $duplicateIds = $dupes->pluck('id')->slice(1)->values();

                if ($duplicateIds->isNotEmpty()) {
                    ServerService::query()
                        ->whereIn('server_id', $duplicateIds->all())
                        ->update(['server_id' => $canonical->id]);

                    Domain::query()
                        ->whereIn('server_id', $duplicateIds->all())
                        ->update(['server_id' => $canonical->id]);

                    Server::query()->whereIn('id', $duplicateIds->all())->delete();
                }
            }

            $attributes = [
                'credential_id' => $this->credential->id,
                'server_id' => $providerServerId,
            ];

            $values = [
                'provider_credential_id' => $this->credential->id,
                'provider_server_id' => $providerServerId,
                'connection_type' => 'provider',
                'name' => Arr::get($server, 'name'),
                'ip_address' => Arr::get($server, 'networks.public_v4'),
                'ip_address_v6' => Arr::get($server, 'networks.public_v6'),
                'internal_ip_address' => Arr::get($server, 'networks.private_v4'),
                'internal_ip_address_v6' => Arr::get($server, 'networks.private_v6'),
                'os' => Arr::get($server, 'image'),
                'vcpu' => Arr::get($server, 'cpu'),
                'memory' => Arr::get($server, 'memory'),
                'disk' => Arr::get($server, 'disk'),
                'cost_per_hour' => Arr::get($server, 'cost'),
                'status' => Arr::get($server, 'status', 'unknown'),
            ];

            Server::query()->updateOrCreate($attributes, $values);
        }

        // Prune servers that no longer exist upstream.
        Server::query()
            ->where('credential_id', $this->credential->id)
            ->where(function ($q): void {
                $q->whereNull('connection_type')
                    ->orWhere('connection_type', 'provider');
            })
            ->whereNotIn('server_id', $remoteServerIds)
            ->delete();
    }
}
