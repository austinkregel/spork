<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Credential;
use App\Models\User;
use App\Services\SshKeyGeneratorService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class Initialize extends Command
{
    protected $signature = 'app:initialize';

    protected $description = 'Initialize the application';

    public function handle()
    {
        if (Credential::where('type', Credential::TYPE_SSH)->exists()) {
            $this->info('SSH key already exists');

            return;
        }

        if (! User::exists()) {
            $this->call('make:user');
        }

        $randomName = Str::random(16);
        $passKey = Str::random(16);

        [$privateKey, $publicKey] = SshKeyGeneratorService::generate($passKey);

        $publicKeyFile = storage_path('app/keys/'.$randomName.'.pub');
        $privateKeyFile = storage_path('app/keys/'.$randomName);

        file_put_contents($publicKeyFile, $publicKey);
        chmod($publicKeyFile, 0600);
        file_put_contents($privateKeyFile, $privateKey);
        chmod($privateKeyFile, 0600);

        Credential::create([
            'service' => Credential::TYPE_SSH,
            'type' => Credential::TYPE_SSH,
            'name' => 'SSH',
            'user_id' => User::first()->id,
            'api_key' => Str::random(32),
            'settings' => [
                'pub_key' => $publicKey,
                'pub_key_file' => $publicKeyFile,
                'private_key' => encrypt($privateKey),
                'private_key_file' => $privateKeyFile,
                'pass_key' => ! empty($passKey) ? encrypt($passKey) : '',
            ],
        ]);
    }
}
