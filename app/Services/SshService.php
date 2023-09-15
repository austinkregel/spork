<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Credential;
use App\Models\Spork\Script;
use Exception;
use Illuminate\Support\Str;

class SshService
{
    protected mixed $connection;

    public function __construct(
        protected string $host,
        protected string $username,
        protected string $publicKeyFile,
        protected string $privateKeyFile,
        protected int $port = 22,
        protected ?string $passKey = null,
    ) {
        $this->connection = ssh2_connect($this->host, $this->port);

        if (! $this->connection) {
            throw new Exception('Connection failed.');
        }

//        try {
            ssh2_auth_pubkey_file($this->connection, $username, $publicKeyFile, $privateKeyFile, $passKey);
//        } catch (\Throwable $e) {
//            throw new Exception('Public key authentication failed. ' . $this->username . '@' . $this->host . ':' . $this->port);
//        }
    }

    public function __destruct()
    {
        if (is_resource($this->connection)) {
            ssh2_disconnect($this->connection);
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

    public function run(Script $script, string $directory = ''): array
    {
        $localFilePath = storage_path('scripts/' . Str::slug($script->name) . '_server.sh');

        file_put_contents($localFilePath, $script->script);

        // Ensure our .basement folder exists
        ssh2_exec($this->connection, "mkdir /tmp/.spork -f");
        stream_set_blocking($this->connection, true);

        // Create a local copy of our script to make sure it runs like we'd expect.
        ssh2_scp_send($this->connection, $localFilePath, $file = "/tmp/.spork/".Str::random(32).'.sh');

        unlink($localFilePath);
        // Run a command that will probably write to stderr (unless you have a folder named /hom)
        $stream_out = ssh2_exec($this->connection, "bash ".escapeshellcmd($file));

        $stream_error = ssh2_fetch_stream($this->connection, SSH2_STREAM_STDERR);
        ssh2_exec($this->connection, "rm $file -f");

        return [
            'stdout' => stream_get_contents($stream_out),
            'stderr' => stream_get_contents($stream_error),
        ];
    }

    /**
     * @throws Exception
     */
    public static function factory(string $host, string $username = 'forge', ?Credential $credential = null): static
    {
        if (! $credential) {
            $randomName = Str::random(16);

            $generatorService = new SshKeyGeneratorService(
                privateKeyFile: $privateKeyFile = storage_path('app/keys/'.$randomName.'.key'),
                publicKeyFile: $publicKeyFile = storage_path('app/keys/'.$randomName.'.pub'),
                passKey: $passKey = ''// Str::random(16),
            );

            $credential = Credential::create([
                'service' => Credential::TYPE_SSH,
                'type' => Credential::TYPE_SSH,
                'name' => 'Forge',
                'user_id' => 1,
                'settings' => [
                    'pub_key' => $generatorService->getPublicKey(),
                    'pub_key_file' => $publicKeyFile,
                    'private_key' => $generatorService->getPrivateKey(),
                    'private_key_file' => $privateKeyFile,
                    'pass_key' => encrypt($passKey),
                ],
            ]);
        }

        return new static(
            host: $host,
            username: $username,
            publicKeyFile: $credential->getPublicKey(),
            privateKeyFile: $credential->getPrivateKey(),
            passKey: $credential->getPasskey()
        );
    }
}
