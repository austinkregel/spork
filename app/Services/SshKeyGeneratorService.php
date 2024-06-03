<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use phpseclib3\Crypt\EC;
use phpseclib3\Crypt\RSA;

class SshKeyGeneratorService
{
    /**
     * Store an encrypted version of the SSH key on the server, and in the databasae.
     */
    public static function generate(
        string $passKey
    ) {

        $key = EC::createKey('ed25519');
        if (!empty($passKey)) {
            $key->withPassword($passKey);
        }

        $privateKey = $key->toString('openssh');
        $publicKey = $key->getPublicKey()->toString('openssh');

        return [$privateKey, $publicKey];
    }

    public function getPrivateKey(): string
    {
        return $this->encryptedPrivateKey;
    }

    public function getPublicKey(): string
    {
        return $this->encryptedPublicKey;
    }
}
