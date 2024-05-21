<?php

declare(strict_types=1);

namespace App\Jobs\Servers;

use App\Services\Development\ForgeDevelopmentService;
use App\Services\Factories\ServerServiceFactory;

class LaravelForgeServersSyncJob extends AbstractSyncServerResourceJob
{
    public function handle(ServerServiceFactory $serviceFactory): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }
        $this->sync();
    }

    public function sync(): void
    {
        // this means all servers need to respond with the keys.
        $servers = (new ForgeDevelopmentService($this->credential))->findAllServers();

        foreach ($servers as $server) {
            $localServer = $this->credential->servers()
                ->where('server_id', $server['id'])
                ->first();

            if (empty($localServer)) {
                $localServer = $this->credential->servers()->create([
                    'server_id' => $server['id'],
                    'name' => $server['name'],
                    'vcpu' => 1,
                    'memory' => 512,
                    'disk' => 25,
                    'ip_address' => $server['ip_address'],
                    'os' => 'Ubuntu',
                    'internal_ip_address' => $server['private_ip_address'],
                ]);
            }

            if ($localServer->isDirty() || ! $localServer->exists()) {
                $localServer->save();
            }

            $localServer->attachTag($server['type'], 'server');
            $localServer->attachTag($server['php_version'], 'server');
        }
    }
}
