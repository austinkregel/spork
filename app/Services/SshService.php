<?php

declare(strict_types=1);

namespace App\Services;

use Exception;

class SshService
{
    protected mixed $connection;

    public function __construct(
        protected string $host,
        protected string $username,
        protected string $publicKeyFile,
        protected string $privateKeyFile,
        protected int $port = 22,
        protected string $passKey = "",
    ) {
        $this->connection = ssh2_connect($this->host, $this->port);

        if (! $this->connection) {
            throw new Exception('Connection failed.');
        }

        if (! ssh2_auth_pubkey_file($this->connection, $this->username, $this->publicKeyFile, $this->privateKeyFile, $this->passKey)) {
             throw new Exception('Public key authentication failed. '.$this->username.'@'.$this->host.':'.$this->port);
        }
    }

    public function execute(string $command, string $directory = ''): string
    {
        if (! empty($directory)) {
            $command = 'cd '.escapeshellarg($directory).' && '.$command;
        }

        $stream = ssh2_exec($this->connection, $command);

        if (! $stream) {
            throw new Exception('Command execution failed.');
        }

        stream_set_blocking($stream, true);
        $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);

        return stream_get_contents($stream_out);
    }
}
