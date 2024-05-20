<?php

declare(strict_types=1);

namespace App\Contracts\Services;

interface ServerServiceContract
{
    public function createServer(array $config): array;

    public function findAllRegions(): array;

    public function findAllSizes(): array;

    public function findAllServers(): array;

    public function removeServerKey($identifier): void;

    public function deleteServer(int|string $identifier): void;

    public function powerOnServer(int|string $identifier): void;

    public function powerOffServer(int|string $identifier): void;

    public function shutdownServer(int|string $identifier): void;

    public function rebootServer(int|string $identifier): void;

    public function findAllSshkeys(): array;

    //    public function changeMemory(int|string $identifier, int $memory): void;
    //    public function changeCpu(int|string $identifier, int $vcpu, int $cores, int $threads): void;
    //    public function changeDisk(int|string $identifier, int $disk_capacity): void;

}
