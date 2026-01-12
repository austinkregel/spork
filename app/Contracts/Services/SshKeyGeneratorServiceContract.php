<?php

declare(strict_types=1);

namespace App\Contracts\Services;

interface SshKeyGeneratorServiceContract
{
    /**
     * Generate SSH key pair
     *
     * @return array{0: string, 1: string} [privateKey, publicKey]
     */
    public static function generate(string $passKey): array;
}
