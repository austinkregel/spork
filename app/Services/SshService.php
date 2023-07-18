<?php

declare(strict_types=1);

namespace App\Services;

use Exception;

class SshService

{
    private mixed $connection;

    public function __construct(
        private string $host,
        private string $username,
        private string $publicKeyFile,
        private string $privateKeyFile,
        private int $port = 22,
    ) {
        $this->connection = ssh2_connect($this->host, $this->port);

        if (!$this->connection) {
            throw new Exception('Connection failed.');
        }

        if (!ssh2_auth_pubkey_file($this->connection, $this->username, $this->publicKeyFile, $this->privateKeyFile)) {
            throw new Exception('Public key authentication failed.');
        }
    }

    public function execute(string $command, string $directory = ''): string
    {
        if (!empty($directory)) {
            $command = 'cd ' . escapeshellarg($directory) . ' && ' . $command;
        }

        $stream = ssh2_exec($this->connection, $command);

        if (!$stream) {
            throw new Exception('Command execution failed.');
        }

        stream_set_blocking($stream, true);
        $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);

        return stream_get_contents($stream_out);
    }
}
