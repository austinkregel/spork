<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Models\Credential;
use App\Models\Server;
use App\Models\Spork\Script;
use App\Models\User;

interface SshServiceContract
{
    public function execute(string $command, string $directory = ''): string;

    public function run(Script $script, string $directory = ''): array;

    public static function fromServer(Server $server): static;

    /**
     * @throws \Exception
     */
    public static function factory(string $host, User $user): Credential;
}
