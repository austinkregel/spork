<?php

declare(strict_types=1);

namespace App\Console\Commands\Infrastructure;

use App\Models\Server;
use Illuminate\Console\Command;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use React\EventLoop\Loop;
use Throwable;

class ListenToCommandServerCommand extends Command
{
    protected $signature = 'infrastructure:command-server-listen {--url=}';

    protected $description = 'Connect to the external command server over WebSocket and update servers by machine_id.';

    public function handle(): int
    {
        $url = (string) ($this->option('url') ?: config('services.command_server.ws_url', ''));

        if ($url === '') {
            $this->error('Missing command server websocket URL. Set COMMAND_SERVER_WS_URL or pass --url=');

            return self::FAILURE;
        }

        $loop = Loop::get();
        $connector = new Connector($loop);

        $connect = function () use (&$connect, $connector, $loop, $url): void {
            $this->info(sprintf('Connecting to command server: %s', $url));

            $connector($url)->then(
                function (WebSocket $connection) use ($loop, &$connect): void {
                    $this->info('Connected.');

                    $connection->on('message', function ($message): void {
                        $payload = json_decode((string) $message, true);

                        if (! is_array($payload)) {
                            return;
                        }

                        $machineId = $payload['machine_id'] ?? $payload['machineId'] ?? null;

                        if (! is_string($machineId) || $machineId === '') {
                            return;
                        }

                        /** @var Server|null $server */
                        $server = Server::query()
                            ->where('machine_id', $machineId)
                            ->first();

                        if (! $server) {
                            return;
                        }

                        $updates = [
                            'last_ping_at' => now(),
                        ];

                        if (isset($payload['status']) && is_string($payload['status'])) {
                            $updates['status'] = $payload['status'];
                        }

                        if (isset($payload['name']) && is_string($payload['name'])) {
                            $updates['name'] = $payload['name'];
                        }

                        if (isset($payload['ip_address']) && is_string($payload['ip_address'])) {
                            $updates['ip_address'] = $payload['ip_address'];
                        }

                        if (isset($payload['os']) && is_string($payload['os'])) {
                            $updates['os'] = $payload['os'];
                        }

                        $server->fill($updates);
                        $server->save();
                    });

                    $connection->on('close', function ($code = null, $reason = null) use ($loop, &$connect): void {
                        $this->warn(sprintf('Disconnected (%s): %s', $code ?? 'n/a', $reason ?? 'no reason'));
                        $loop->addTimer(5, $connect);
                    });
                },
                function (Throwable $e) use ($loop, &$connect): void {
                    $this->error(sprintf('Connection failed: %s', $e->getMessage()));
                    $loop->addTimer(5, $connect);
                },
            );
        };

        $connect();
        $loop->run();

        return self::SUCCESS;
    }
}
