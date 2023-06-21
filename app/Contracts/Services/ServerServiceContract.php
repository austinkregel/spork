<?php

namespace App\Contracts\Services;

interface ServerServiceContract
{
    public function createServer(array $config): array;

    public function findAllRegions(): array;

    public function findAllSizes(): array;

    public function findAllServers(): array;

    public function removeServerKey($identifier): void;

    public function deleteServer(int $identifier): void;

    public function powerOnServer(int $identifier): void;

    public function powerOffServer(int $identifier): void;

    public function shutdownServer(int $identifier): void;

    public function rebootServer(int $identifier): void;

    public function findAllSshkeys(): array;
}
