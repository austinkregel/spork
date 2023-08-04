<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Crypt;

class SshKeyGeneratorService
{
    protected string $privateKey;
    protected string $publicKey;

    /**
     * Store an encrypted version of the SSH key on the server, and in the databasae.
     */
    public function __construct(
        private string $curveName = 'prime256v1',
        protected string $privateKeyFile = '',
        protected string $publicKeyFile = '',
        protected bool $overwrite = false,
        protected string $encryptedPrivateKey = '',
        protected string $encryptedPublicKey = '',
        protected  string $passKey = "",
    ) {
        $res = openssl_pkey_new([
            "curve_name" => $this->curveName,
            "private_key_type" => OPENSSL_KEYTYPE_EC,
        ]);

        if (!$res) {
            throw new Exception('Could not generate the key pair.');
        }

        openssl_pkey_export($res, $privKey, $this->passKey);

        $this->encryptedPrivateKey = $privKey;
        $pubKeyDetails = openssl_pkey_get_details($res);
        unset($privKey);
        unset($res);
        $this->encryptedPublicKey = $pubKeyDetails['key'];

        if (! file_exists($this->privateKeyFile)) {
            file_put_contents($this->privateKeyFile, $this->encryptedPrivateKey);
            chmod($this->privateKeyFile, 0600);
        }

        if (! file_exists($this->publicKeyFile)) {
//            dd(sprintf('echo "%s"', $this->passKey).' && ssh-keygen -y -f '.$this->privateKeyFile.' > '.$this->publicKeyFile);
            file_put_contents($this->publicKeyFile, $this->encryptedPublicKey);
            chmod($this->publicKeyFile, 0600);
        }
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
