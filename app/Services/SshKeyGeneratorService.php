<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Crypt;

class SshKeyGeneratorService
{
    protected string $privateKey;
    protected string $publicKey;

    public function __construct(
        private string $curveName = 'prime256v1',
        protected string $privateKeyFile = '',
        protected string $publicKeyFile = '',
        protected bool $overwrite = false,
        protected string $encryptedPrivateKey = '',
        protected string $encryptedPublicKey = '',
    ) {
        $res = openssl_pkey_new([
            "curve_name" => $this->curveName,
            "private_key_type" => OPENSSL_KEYTYPE_EC,
        ]);

        if (!$res) {
            throw new Exception('Could not generate the key pair.');
        }

        openssl_pkey_export($res, $privKey);

        $this->encryptedPrivateKey = Crypt::encryptString($privKey);
        $pubKeyDetails = openssl_pkey_get_details($res);
        unset($privKey);
        unset($res);
        $this->encryptedPublicKey = Crypt::encryptString($pubKeyDetails['key']);

        if (! file_exists($this->privateKeyFile)) {
            file_put_contents($this->privateKeyFile, $this->encryptedPrivateKey);
            chmod($this->privateKeyFile, 0600);
        }
        if (! file_exists($this->publicKeyFile)) {
            file_put_contents($this->publicKeyFile, $this->encryptedPublicKey);
            chmod($this->publicKeyFile, 0600);
        }
    }

    public function getPrivateKey(): string
    {
        return Crypt::decryptString($this->encryptedPrivateKey);
    }

    public function getPublicKey(): string
    {
        return Crypt::decryptString($this->encryptedPublicKey);
    }
}
